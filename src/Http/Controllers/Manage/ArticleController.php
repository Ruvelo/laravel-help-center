<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers\Manage;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Ruvelo\HelpCenter\Exceptions\ArticleAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\EditConflict;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\Markdown\Renderer;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->string('q')),
            'status' => in_array($request->query('status'), Article::STATUSES, true) ? (string) $request->query('status') : '',
            'category' => (string) $request->string('category'),
        ];

        $articles = Article::query()
            ->with('category:id,name,slug,icon')
            ->when($filters['q'] !== '', fn ($query) => $query->matching($filters['q']), fn ($query) => $query->orderByDesc('updated_at'))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['category'] === 'none', fn ($query) => $query->whereNull('category_id'))
            ->when($filters['category'] !== '' && $filters['category'] !== 'none', fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $filters['category'])))
            ->paginate(config('help-center.per_page', 20), ['id', 'category_id', 'title', 'slug', 'status', 'views', 'helpful_count', 'not_helpful_count', 'updated_at'])
            ->withQueryString();

        return response()->view('help::manage.index', [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => $this->categories(),
            'counts' => Article::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($total) => (int) $total),
        ]);
    }

    public function create(Request $request): Response
    {
        $article = new Article([
            'title' => (string) $request->string('title'),
            'category_id' => Category::query()->where('slug', (string) $request->string('category'))->value('id'),
        ]);

        return $this->form($article, '');
    }

    public function store(Request $request, Renderer $renderer): Response|RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->input('action') === 'preview') {
            return $this->form((new Article)->forceFill($data), $renderer->render($data['body']));
        }

        try {
            $article = (new Article)->commit($data);
        } catch (InvalidTitle|ArticleAlreadyExists $e) {
            throw ValidationException::withMessages(['title' => $e instanceof ArticleAlreadyExists ? 'An article with this title already exists.' : $e->getMessage()]);
        }

        return redirect()->route('help.manage.articles.edit', $article)
            ->with('help.status', $article->isPublished() ? 'Article published.' : 'Draft saved.');
    }

    public function edit(Article $article): Response
    {
        return $this->form($article, $article->html());
    }

    /**
     * The editor's live preview: Markdown in, HTML fragment out.
     */
    public function preview(Request $request, Renderer $renderer): Response
    {
        $data = $request->validate(['body' => ['nullable', 'string']]);

        return response($renderer->render($data['body'] ?? ''))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function update(Request $request, Article $article, Renderer $renderer): Response|RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->input('action') === 'preview') {
            return $this->form($article->forceFill($data), $renderer->render($data['body']), $request->integer('version'));
        }

        $wasPublished = $article->isPublished();

        try {
            $article->commit($data, $request->integer('version'));
        } catch (InvalidTitle $e) {
            throw ValidationException::withMessages(['title' => $e->getMessage()]);
        } catch (EditConflict) {
            // The form comes back with the editor's text intact.
            throw ValidationException::withMessages([
                'body' => 'Someone else saved this article while you were editing it. Copy your changes, reload the page, and apply them again.',
            ]);
        }

        $status = match (true) {
            ! $article->wasChanged() => 'No changes to save.',
            $article->isPublished() && ! $wasPublished => 'Article published.',
            ! $article->isPublished() && $wasPublished => 'Article unpublished: it is a draft again.',
            default => 'Changes saved.',
        };

        return redirect()->route('help.manage.articles.edit', $article)->with('help.status', $status);
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('help.manage.articles.index')->with('help.status', "Deleted “{$article->title}”.");
    }

    private function form(Article $article, string $preview, ?int $version = null): Response
    {
        return response()->view('help::manage.edit', [
            'article' => $article,
            'categories' => $this->categories(),
            'preview' => $preview,
            'version' => $version ?? $article->version,
        ]);
    }

    /**
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        return Category::query()->orderBy('position')->orderBy('name')->get(['id', 'name', 'slug', 'icon']);
    }

    /**
     * @return array{title: string, body: string, excerpt: string|null, status: string, category_id: int|null, position?: int}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(Article::STATUSES)],
            'category_id' => ['nullable', 'integer', Rule::exists((new Category)->getTable(), 'id')],
            'position' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $validated = [
            'title' => trim((string) $data['title']),
            'body' => str_replace("\r\n", "\n", (string) ($data['body'] ?? '')),
            'excerpt' => isset($data['excerpt']) ? (string) $data['excerpt'] : null,
            'status' => (string) $data['status'],
            'category_id' => isset($data['category_id']) ? (int) $data['category_id'] : null,
        ];

        if (isset($data['position'])) {
            $validated['position'] = (int) $data['position'];
        }

        return $validated;
    }
}
