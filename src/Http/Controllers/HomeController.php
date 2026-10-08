<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $editor = HelpCenter::canEdit($request->user());

        $categories = Category::query()
            ->withCount(['articles as published_articles_count' => fn (Builder $query) => $query->where('status', Article::PUBLISHED)])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->when(! $editor, fn ($categories) => $categories->filter(fn (Category $category) => $category->published_articles_count > 0)->values());

        $limit = (int) config('help-center.popular', 6);
        $popular = $limit > 0
            ? Article::query()->published()->with('category:id,name,slug,icon')
                ->orderByDesc('views')->orderBy('title')->limit($limit)
                ->get(['id', 'category_id', 'title', 'slug', 'excerpt', 'body', 'views'])
            : collect();

        return response()->view('help::home', ['categories' => $categories, 'popular' => $popular]);
    }
}
