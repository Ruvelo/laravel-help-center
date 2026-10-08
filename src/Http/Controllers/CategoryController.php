<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Support\Menu;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function __invoke(Request $request, Category $category): Response
    {
        $articles = Article::query()
            ->where('category_id', $category->id)
            ->visibleTo($request->user())
            ->ordered()
            ->get();

        return response()->view('help::category', [
            'category' => $category,
            'articles' => $articles,
            'menu' => Menu::for($request->user()),
        ]);
    }
}
