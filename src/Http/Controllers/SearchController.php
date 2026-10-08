<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Support\Menu;
use Ruvelo\HelpCenter\Support\Snippet;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $query = self::query($request);

        if ($query === '') {
            return response()->view('help::search', ['query' => '', 'articles' => null, 'menu' => Menu::for($request->user())]);
        }

        // An exact title goes straight to the article.
        $exact = $this->exact($request, $query);
        if ($exact !== null && ! $request->boolean('all')) {
            return redirect()->route('help.articles.show', $exact);
        }

        $articles = $this->results($request, $query)
            ->paginate(config('help-center.per_page', 20))
            ->withQueryString();

        return response()->view('help::search', ['query' => $query, 'articles' => $articles, 'menu' => Menu::for($request->user())]);
    }

    /**
     * Suggestions for the search box, as you type.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = self::query($request);

        $articles = mb_strlen($query) < 2 ? collect() : $this->results($request, $query)->limit(6)->get();

        return response()->json([
            'query' => $query,
            'results' => $articles->map(fn (Article $article) => [
                'title' => $article->title,
                'title_html' => Snippet::highlight($article->title, $query),
                'url' => route('help.articles.show', $article),
                'category' => $article->category?->name,
                'snippet_html' => Snippet::for($article, $query),
            ])->values(),
            'all_url' => route('help.search', ['q' => $query, 'all' => 1]),
        ]);
    }

    private static function query(Request $request): string
    {
        $query = $request->query('q', '');

        return is_string($query) ? mb_substr(trim($query), 0, 200) : '';
    }

    private function exact(Request $request, string $query): ?Article
    {
        $slug = Article::slugFor($query);

        return $slug === '' ? null : Article::query()->visibleTo($request->user())
            ->where(fn (Builder $where) => $where->where('slug', $slug)->orWhereRaw('lower(title) = ?', [mb_strtolower($query)]))
            ->first();
    }

    /**
     * @return Builder<Article>
     */
    private function results(Request $request, string $query): Builder
    {
        return Article::query()->visibleTo($request->user())->with('category:id,name,slug,icon')->matching($query);
    }
}
