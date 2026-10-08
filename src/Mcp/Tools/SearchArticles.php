<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Ruvelo\HelpCenter\Mcp\Presenter;
use Ruvelo\HelpCenter\Models\Article;

#[Name('search_articles')]
#[Title('Search help articles')]
#[Description('Search the published help center articles. Returns the best matches first, each with its title, slug, a short excerpt, its category and a public URL. Use read_article with a slug to get the full text before answering.')]
#[IsReadOnly]
#[IsIdempotent]
final class SearchArticles extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $query = Presenter::query($data['query']);
        $category = is_string($data['category'] ?? null) ? Presenter::findCategory($data['category']) : null;

        if (is_string($data['category'] ?? null) && $category === null) {
            return Response::error("There is no category “{$data['category']}”. Call list_categories to see them.");
        }

        $articles = Article::query()
            ->published()
            ->with('category')
            ->when($category !== null, fn ($builder) => $builder->where('category_id', $category?->id))
            ->matching($query)
            ->limit(is_int($data['limit'] ?? null) ? $data['limit'] : 5)
            ->get();

        return Response::structured([
            'query' => $query,
            'count' => $articles->count(),
            'articles' => $articles->map(Presenter::summary(...))->values()->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Words to look for, e.g. "refund annual plan". Every word must appear in the article.')->required(),
            'category' => $schema->string()->description('Only search this category (its slug or name).'),
            'limit' => $schema->integer()->min(1)->max(20)->default(5)->description('How many articles to return, 1–20.'),
        ];
    }
}
