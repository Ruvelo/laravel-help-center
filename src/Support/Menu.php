<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

/**
 * The left-hand menu: categories in order, each with the articles the
 * reader can see. Categories with nothing to read are left out for
 * readers, and kept for editors so they can fill them.
 */
final class Menu
{
    /**
     * @return Collection<int, Category>
     */
    public static function for(?Authenticatable $user): Collection
    {
        $editor = HelpCenter::canEdit($user);

        $categories = Category::query()
            ->with(['articles' => fn (HasMany $query) => $query
                ->when(! $editor, fn ($articles) => $articles->where('status', Article::PUBLISHED))
                ->select(['id', 'category_id', 'title', 'slug', 'status', 'position'])])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $editor ? $categories : $categories->filter(fn (Category $category) => $category->articles->isNotEmpty())->values();
    }
}
