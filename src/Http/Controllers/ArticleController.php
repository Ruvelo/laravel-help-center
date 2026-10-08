<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Feedback;
use Ruvelo\HelpCenter\Support\Menu;
use Ruvelo\HelpCenter\Support\Outline;
use Ruvelo\HelpCenter\Support\Visitor;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends Controller
{
    public function show(Request $request, Article $article): Response
    {
        $editor = HelpCenter::canEdit($request->user());
        abort_unless($article->isPublished() || $editor, 404);

        if (! $editor && $article->isPublished() && ! Visitor::isBot($request) && $request->isMethod('GET')) {
            $article->recordView();
        }

        $html = $article->html();
        $visitor = Visitor::find($request);

        $related = $article->category_id === null ? collect() : Article::query()
            ->published()
            ->where('category_id', $article->category_id)
            ->whereKeyNot($article->id)
            ->ordered()
            ->limit(5)
            ->get(['id', 'title', 'slug']);

        return response()->view('help::article', [
            'article' => $article->loadMissing('category'),
            'html' => $html,
            'outline' => Outline::of($html),
            'menu' => Menu::for($request->user()),
            'related' => $related,
            'feedbackState' => self::state($visitor === null ? null : $article->feedbackFrom($visitor)),
        ]);
    }

    /**
     * "Was this article helpful?" Works as a plain form post (redirects
     * back) and as JSON for the inline script.
     */
    public function feedback(Request $request, Article $article): JsonResponse|RedirectResponse
    {
        abort_unless($article->isPublished() || HelpCenter::canEdit($request->user()), 404);

        $data = $request->validate([
            'helpful' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $feedback = $article->recordFeedback(Visitor::get($request), (bool) $data['helpful'], $data['comment'] ?? null);
        $state = self::state($feedback);

        if ($request->expectsJson()) {
            return response()->json(['state' => $state]);
        }

        return redirect()->to(route('help.articles.show', $article).'#feedback')
            ->with('help.status', $state === 'no' ? 'Thanks for telling us. What were you looking for?' : 'Thanks for your feedback.');
    }

    /**
     * Where the visitor is in the feedback box: not voted yet ("ask"), said
     * yes ("yes"), said no and may add a comment ("no"), or all done.
     */
    private static function state(?Feedback $feedback): string
    {
        return match (true) {
            $feedback === null => 'ask',
            $feedback->helpful => 'yes',
            $feedback->comment === null => 'no',
            default => 'done',
        };
    }
}
