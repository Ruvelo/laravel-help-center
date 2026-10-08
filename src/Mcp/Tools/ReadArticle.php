<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Ruvelo\HelpCenter\Mcp\Presenter;

#[Name('read_article')]
#[Title('Read a help article')]
#[Description('Get one help center article by its slug: the full text as Markdown, its title, category, public URL, version and when it was last updated. Quote the URL when you answer so people can read the whole article.')]
#[IsReadOnly]
#[IsIdempotent]
final class ReadArticle extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate(['slug' => ['required', 'string', 'max:255']]);
        $slug = (string) $data['slug'];

        $article = Presenter::findArticle($slug, $request->user());

        if ($article === null) {
            return Response::error("There is no published article “{$slug}”. Use search_articles to find the right slug.");
        }

        return Response::structured(Presenter::full($article));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The article\'s slug, as search_articles returns it, e.g. "download-an-invoice".')->required(),
        ];
    }
}
