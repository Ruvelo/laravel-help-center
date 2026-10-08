<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use League\CommonMark\Environment\Environment;
use Ruvelo\HelpCenter\Exceptions\ArticleAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\CategoryAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\CategoryNotFound;
use Ruvelo\HelpCenter\Exceptions\EditConflict;
use Ruvelo\HelpCenter\Exceptions\InvalidStatus;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\Markdown\Renderer;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

/**
 * The help center's public PHP API.
 *
 *     HelpCenter::category('Billing & invoices', icon: '🧾');
 *     HelpCenter::publish('Download an invoice', $markdown, 'billing-invoices');
 *     HelpCenter::article('download-an-invoice')?->html();
 *     HelpCenter::search('refund')->limit(5)->get();
 */
final class HelpCenter
{
    /** @var list<Closure(Environment): void> */
    private static array $markdownExtenders = [];

    /**
     * A published article by its slug (or title). Pass $drafts to find
     * drafts too.
     */
    public static function article(string $slugOrTitle, bool $drafts = false): ?Article
    {
        $slug = Article::slugFor($slugOrTitle);

        return $slug === '' ? null : Article::query()
            ->where('slug', $slug)
            ->unless($drafts, fn (Builder $query) => $query->where('status', Article::PUBLISHED))
            ->first();
    }

    /**
     * Published articles containing every word of the text, title matches
     * first.
     *
     * @return Builder<Article>
     */
    public static function search(string $text): Builder
    {
        return Article::query()->published()->matching($text);
    }

    /**
     * Every category in order, with `published_articles_count`.
     *
     * @return Collection<int, Category>
     */
    public static function categories(): Collection
    {
        return Category::query()
            ->withCount(['articles as published_articles_count' => fn (Builder $query) => $query->where('status', Article::PUBLISHED)])
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a category by slug or name, or create it. Given details update it.
     *
     * @throws InvalidTitle
     * @throws CategoryAlreadyExists
     */
    public static function category(string $name, ?string $icon = null, ?string $description = null, ?int $position = null): Category
    {
        $category = self::findCategory($name) ?? new Category;

        $changes = array_filter(
            ['name' => $category->exists ? null : $name, 'icon' => $icon, 'description' => $description, 'position' => $position],
            fn ($value) => $value !== null,
        );

        return $category->commit($changes);
    }

    /**
     * Create the article as published, or save and publish the existing one
     * with the same title.
     *
     * @param  Category|string|null  $category  a category, its slug or name
     *
     * @throws InvalidTitle
     * @throws CategoryNotFound
     * @throws EditConflict
     */
    public static function publish(string $title, string $body, Category|string|null $category = null, ?string $excerpt = null, ?int $position = null): Article
    {
        return self::write($title, $body, $category, Article::PUBLISHED, $excerpt, $position);
    }

    /**
     * Create the article, or save changes to the one with the same title.
     * New articles are drafts unless you say otherwise; an existing article
     * keeps its status, category and position unless you pass new ones.
     *
     * @param  Category|string|null  $category  a category, its slug or name
     *
     * @throws InvalidTitle
     * @throws InvalidStatus
     * @throws CategoryNotFound
     * @throws EditConflict
     */
    public static function write(string $title, string $body, Category|string|null $category = null, ?string $status = null, ?string $excerpt = null, ?int $position = null): Article
    {
        $article = self::article($title, drafts: true) ?? new Article;

        $changes = ['title' => $title, 'body' => $body];

        if ($category !== null) {
            $changes['category_id'] = self::resolveCategory($category)->id;
        }
        foreach (['status' => $status, 'excerpt' => $excerpt, 'position' => $position] as $key => $value) {
            if ($value !== null) {
                $changes[$key] = $value;
            }
        }

        try {
            return $article->commit($changes);
        } catch (ArticleAlreadyExists $e) {
            // Same slug, different spelling of the title: update that one.
            return $e->article->commit($changes);
        }
    }

    /**
     * Markdown to safe HTML, with [[article links]] resolved.
     */
    public static function render(string $markdown): string
    {
        return app(Renderer::class)->render($markdown);
    }

    /**
     * Whether the user may manage articles, categories and feedback.
     *
     * Define a `help-center-edit` gate to decide; until you do, no one can.
     */
    public static function canEdit(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && Gate::has('help-center-edit') && Gate::forUser($user)->allows('help-center-edit');
    }

    /**
     * Customise the Markdown pipeline: add CommonMark extensions, parsers or
     * renderers. Call it from a service provider's boot method.
     *
     * @param  Closure(Environment): void  $callback
     */
    public static function extendMarkdown(Closure $callback): void
    {
        self::$markdownExtenders[] = $callback;
        app(Renderer::class)->reset();
    }

    /**
     * @internal
     *
     * @return list<Closure(Environment): void>
     */
    public static function markdownExtenders(): array
    {
        return self::$markdownExtenders;
    }

    /**
     * The view every help center page extends: the package's own page, or
     * a bridge into the host app's layout.
     *
     * @internal
     */
    public static function layout(): string
    {
        return config('help-center.layout') ? 'help::embedded' : 'help::layout';
    }

    /**
     * Forget registered extensions. For tests and long-running workers.
     */
    public static function flush(): void
    {
        self::$markdownExtenders = [];
        app(Renderer::class)->reset();
    }

    private static function findCategory(string $slugOrName): ?Category
    {
        $slug = Article::slugFor($slugOrName);

        return $slug === '' ? null : Category::query()->where('slug', $slug)->orWhere('name', $slugOrName)->first();
    }

    /**
     * @throws CategoryNotFound
     */
    private static function resolveCategory(Category|string $category): Category
    {
        if ($category instanceof Category) {
            return $category;
        }

        return self::findCategory($category) ?? throw new CategoryNotFound($category);
    }
}
