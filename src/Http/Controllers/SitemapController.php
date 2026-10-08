<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers;

use Illuminate\Routing\Controller;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * sitemap.xml: the home page, categories with something to read, and
 * every published article.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $articles = Article::query()->published()->orderBy('id')->get(['id', 'category_id', 'slug', 'updated_at']);

        $categories = Category::query()
            ->whereIn('id', $articles->pluck('category_id')->filter()->unique())
            ->orderBy('position')
            ->get(['id', 'slug', 'updated_at']);

        return response()
            ->view('help::sitemap', ['articles' => $articles, 'categories' => $categories])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
