<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers\Manage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Feedback;
use Symfony\Component\HttpFoundation\Response;

/**
 * What readers said: the articles with the most "No" votes, and the
 * comments left with them, newest first.
 */
class FeedbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $article = Article::query()->where('slug', (string) $request->string('article'))->first();

        $comments = Feedback::query()
            ->with('article:id,title,slug')
            ->where('helpful', false)
            ->whereNotNull('comment')
            ->when($article, fn (Builder $query, Article $article) => $query->where('article_id', $article->id))
            ->latest()
            ->orderByDesc('id')
            ->paginate(config('help-center.per_page', 20))
            ->withQueryString();

        $leastHelpful = Article::query()
            ->where('not_helpful_count', '>', 0)
            ->orderByDesc('not_helpful_count')
            ->orderBy('title')
            ->limit(8)
            ->get(['id', 'title', 'slug', 'helpful_count', 'not_helpful_count']);

        return response()->view('help::manage.feedback', [
            'comments' => $comments,
            'leastHelpful' => $leastHelpful,
            'article' => $article,
        ]);
    }
}
