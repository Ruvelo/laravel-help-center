<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Mcp;

use Laravel\Mcp\Server;
use Ruvelo\HelpCenter\Mcp\Resources\ArticleResource;
use Ruvelo\HelpCenter\Mcp\Tools\ListCategories;
use Ruvelo\HelpCenter\Mcp\Tools\ReadArticle;
use Ruvelo\HelpCenter\Mcp\Tools\SearchArticles;
use Ruvelo\HelpCenter\Mcp\Tools\WriteArticle;

/**
 * The help center as an MCP server, for support agents' AI assistants and
 * customer-facing chatbots. Needs laravel/mcp; switch it on with
 * `help-center.mcp.enabled`.
 *
 * Extend it to add your own tools, then point `help-center.mcp.server` at
 * your class.
 */
class HelpCenterServer extends Server
{
    protected string $version = '1.1.0';

    protected array $tools = [
        SearchArticles::class,
        ReadArticle::class,
        ListCategories::class,
        WriteArticle::class,
    ];

    protected array $resources = [
        ArticleResource::class,
    ];

    protected function boot(): void
    {
        $name = config('help-center.name', 'Help Center');
        $this->name = is_string($name) && $name !== '' ? $name : 'Help Center';

        $this->instructions = <<<MARKDOWN
            This server is the {$this->name}: the published help articles for the product, grouped in categories.

            To answer a question, call search_articles with a few key words, then read_article on the best match and answer from its text. Keep answers short, quote the article's URL so people can read more, and say so plainly when no article covers the question instead of guessing.

            list_categories shows what the help center covers. Editors may also see write_article, which saves drafts or published articles.
            MARKDOWN;
    }
}
