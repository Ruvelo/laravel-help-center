<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Ruvelo\HelpCenter\Exceptions\CategoryAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\CategoryNotEmpty;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Http\Resources\CategoryResource;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

class CategoryController extends Controller
{
    /**
     * GET /categories, with the number of published articles in each.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(HelpCenter::categories());
    }

    /**
     * GET /categories/{slug}, with its published articles.
     */
    public function show(Category $category): CategoryResource
    {
        $category->load(['articles' => fn (HasMany $query) => $query->where('status', Article::PUBLISHED)->with('category')])
            ->loadCount(['articles as published_articles_count' => fn (Builder $query) => $query->where('status', Article::PUBLISHED)]);

        return new CategoryResource($category);
    }

    /**
     * POST /categories {name, icon?, description?, position?}
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $category = (new Category)->commit($this->validated($request, creating: true));
        } catch (InvalidTitle $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['name' => [$e->getMessage()]]], 422);
        } catch (CategoryAlreadyExists $e) {
            return response()->json(['message' => $e->getMessage(), 'category' => route('help.api.categories.show', $e->category)], 409);
        }

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * PATCH /categories/{slug} {name?, icon?, description?, position?}
     */
    public function update(Request $request, Category $category): CategoryResource|JsonResponse
    {
        try {
            $category->commit($this->validated($request, creating: false));
        } catch (InvalidTitle $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['name' => [$e->getMessage()]]], 422);
        }

        return new CategoryResource($category);
    }

    /**
     * DELETE /categories/{slug}: only when it has no articles (else 409).
     */
    public function destroy(Category $category): Response|JsonResponse
    {
        try {
            $category->remove();
        } catch (CategoryNotEmpty $e) {
            return response()->json(['message' => $e->getMessage(), 'articles' => $e->articles], 409);
        }

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:16'],
            'description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);
    }
}
