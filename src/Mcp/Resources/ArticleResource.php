<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;
use Ruvelo\HelpCenter\Mcp\Presenter;

/**
 * Every published article as a Markdown resource: help-center://articles/{slug}.
 */
#[Name('article')]
#[Title('Help article')]
#[Description('A help center article as Markdown, by slug: help-center://articles/{slug}.')]
#[MimeType('text/markdown')]
final class ArticleResource extends Resource implements HasUriTemplate
{
    public const URI = 'help-center://articles/{slug}';

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate(self::URI);
    }

    public function handle(Request $request): Response
    {
        $slug = rawurldecode((string) $request->string('slug'));
        $article = Presenter::findArticle($slug, $request->user());

        if ($article === null) {
            return Response::error("There is no published article “{$slug}”.");
        }

        return Response::text(Presenter::markdown($article));
    }
}
