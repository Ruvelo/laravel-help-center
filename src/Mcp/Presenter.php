<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Mcp;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

/**
 * The shapes the MCP tools and resources answer with, kept in one place so
 * every tool describes an article the same way.
 *
 * @internal
 */
final class Presenter
{
    /**
     * The article a user may read: published, or a draft for editors.
     */
    public static function findArticle(string $slug, ?Authenticatable $user): ?Article
    {
        $slug = Article::slugFor($slug);

        return $slug === '' ? null : Article::query()
            ->visibleTo($user)
            ->with('category')
            ->where('slug', $slug)
            ->first();
    }

    /**
     * A category by slug or name.
     */
    public static function findCategory(string $slugOrName): ?Category
    {
        $slug = Article::slugFor($slugOrName);

        return $slug === '' ? null : Category::query()
            ->where('slug', $slug)
            ->orWhere('name', trim($slugOrName))
            ->first();
    }

    /**
     * @return array{title: string, slug: string, excerpt: string, category: array{name: string, slug: string}|null, url: string|null}
     */
    public static function summary(Article $article): array
    {
        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->summary(),
            'category' => self::categoryRef($article->category),
            'url' => self::articleUrl($article),
        ];
    }

    /**
     * Everything an agent needs to quote or edit the article. Status and
     * version only matter to editors, but they reveal nothing on a
     * published article.
     *
     * @return array<string, mixed>
     */
    public static function full(Article $article): array
    {
        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'status' => $article->status,
            'version' => $article->version,
            'category' => self::categoryRef($article->category),
            'url' => self::articleUrl($article),
            'excerpt' => $article->summary(),
            'markdown' => $article->body,
            'updated_at' => $article->updated_at->toIso8601String(),
        ];
    }

    /**
     * The article as one Markdown document: a title, a short source line,
     * then the body.
     */
    public static function markdown(Article $article): string
    {
        $source = array_filter([
            $article->category?->name,
            $article->isPublished() ? null : 'Draft',
            'Updated '.$article->updated_at->toDateString(),
            self::articleUrl($article),
        ]);

        return "# {$article->title}\n\n_".implode(' · ', $source)."_\n\n".$article->body;
    }

    /**
     * @return array{name: string, slug: string, icon: string|null, description: string|null, url: string|null, article_count: int}
     */
    public static function category(Category $category): array
    {
        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'icon' => $category->icon,
            'description' => $category->description,
            'url' => Route::has('help.categories.show') ? route('help.categories.show', $category) : null,
            'article_count' => (int) $category->published_articles_count,
        ];
    }

    /**
     * Whether the user may write articles through MCP: the config allows it
     * and the `help-center-edit` gate lets them.
     */
    public static function canWrite(?Authenticatable $user): bool
    {
        return (bool) config('help-center.mcp.allow_writes', false) && HelpCenter::canEdit($user);
    }

    /**
     * Search words are capped like the JSON API's.
     */
    public static function query(mixed $text): string
    {
        return mb_substr(trim(is_scalar($text) ? (string) $text : ''), 0, 200);
    }

    /**
     * @return array{name: string, slug: string}|null
     */
    private static function categoryRef(?Category $category): ?array
    {
        return $category === null ? null : ['name' => $category->name, 'slug' => $category->slug];
    }

    private static function articleUrl(Article $article): ?string
    {
        // Drafts have no public page, and the host app may serve its own routes.
        return $article->isPublished() && Route::has('help.articles.show') ? route('help.articles.show', $article) : null;
    }
}
