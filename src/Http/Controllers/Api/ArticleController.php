<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Ruvelo\HelpCenter\Exceptions\ArticleAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\EditConflict;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\Http\Resources\ArticleResource;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

class ArticleController extends Controller
{
    /**
     * GET /articles?category=billing, published only, in menu order.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $category = (string) $request->string('category');

        $articles = Article::query()
            ->published()
            ->with('category')
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($where) => $where->where('slug', $category)))
            ->ordered()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ArticleResource::collection($articles);
    }

    /**
     * GET /search?q=refund, published only, with highlighted snippets.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $query = mb_substr(trim((string) $request->string('q')), 0, 200);

        $articles = Article::query()
            ->published()
            ->with('category')
            ->matching($query)
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ArticleResource::collection($articles->through(fn (Article $article) => (new ArticleResource($article))->matching($query)));
    }

    /**
     * GET /articles/{slug}: a published article, with body, html and outline.
     */
    public function show(string $slug): ArticleResource
    {
        $article = Article::query()->published()->with('category')->where('slug', $slug)->firstOrFail();

        return (new ArticleResource($article))->full();
    }

    /**
     * POST /articles {title, body?, category?, status?, excerpt?, position?}
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, creating: true);

        try {
            $article = (new Article)->commit($data);
        } catch (InvalidTitle $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['title' => [$e->getMessage()]]], 422);
        } catch (ArticleAlreadyExists $e) {
            return response()->json(['message' => $e->getMessage(), 'article' => $e->article->slug], 409);
        }

        return (new ArticleResource($article->load('category')))->full()->response()->setStatusCode(201);
    }

    /**
     * PATCH /articles/{slug} {title?, body?, category?, status?, excerpt?, position?, base_version?}
     *
     * Send base_version (the "version" you last read) to be refused with a
     * 409 instead of overwriting someone else's newer save.
     */
    public function update(Request $request, Article $article): ArticleResource|JsonResponse
    {
        $data = $this->validated($request, creating: false);
        $base = $request->has('base_version') ? $request->integer('base_version') : null;

        try {
            $article->commit($data, $base);
        } catch (InvalidTitle $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['title' => [$e->getMessage()]]], 422);
        } catch (EditConflict $e) {
            return response()->json(['message' => 'The article changed since base_version.', 'current_version' => $e->currentVersion], 409);
        }

        return (new ArticleResource($article->load('category')))->full();
    }

    /**
     * DELETE /articles/{slug}
     */
    public function destroy(Article $article): Response
    {
        $article->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(Article::STATUSES)],
            'category' => ['sometimes', 'nullable', 'string', Rule::exists((new Category)->getTable(), 'slug')],
            'position' => ['sometimes', 'integer', 'min:0'],
            'base_version' => ['sometimes', 'integer'],
        ]);

        if (array_key_exists('category', $data)) {
            $data['category_id'] = $data['category'] === null ? null : Category::query()->where('slug', $data['category'])->value('id');
            unset($data['category']);
        }

        if (array_key_exists('body', $data)) {
            $data['body'] ??= '';
        }

        unset($data['base_version']);

        return $data;
    }

    private function perPage(Request $request): int
    {
        return max(1, min($request->integer('per_page', (int) config('help-center.per_page', 20)), 100));
    }
}
