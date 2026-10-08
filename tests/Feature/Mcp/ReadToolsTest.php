<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

use Illuminate\Routing\RouteCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Ruvelo\HelpCenter\Mcp\HelpCenterServer;
use Ruvelo\HelpCenter\Mcp\Resources\ArticleResource;
use Ruvelo\HelpCenter\Mcp\Tools\ListCategories;
use Ruvelo\HelpCenter\Mcp\Tools\ReadArticle;
use Ruvelo\HelpCenter\Mcp\Tools\SearchArticles;
use Ruvelo\HelpCenter\Models\Article;

class ReadToolsTest extends McpTestCase
{
    public function test_search_returns_published_matches_with_title_slug_excerpt_category_and_url(): void
    {
        $billing = $this->category('Billing');
        $this->article('Request a refund', "Refunds take five days.\n\nWrite to us.", $billing, attributes: ['excerpt' => 'Money back within 30 days.']);
        $this->article('Refund policy draft', 'Refunds are changing.', $billing, Article::DRAFT);
        $this->article('Change your password', 'Nothing to see.');

        HelpCenterServer::tool(SearchArticles::class, ['query' => 'refund'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('query', 'refund')
                ->where('count', 1)
                ->has('articles', 1)
                ->where('articles.0', [
                    'title' => 'Request a refund',
                    'slug' => 'request-a-refund',
                    'excerpt' => 'Money back within 30 days.',
                    'category' => ['name' => 'Billing', 'slug' => 'billing'],
                    'url' => route('help.articles.show', 'request-a-refund'),
                ]))
            ->assertDontSee('Refund policy draft');
    }

    public function test_search_never_shows_drafts_even_to_editors(): void
    {
        $this->article('Refund policy draft', 'Refunds are changing.', status: Article::DRAFT);

        HelpCenterServer::actingAs($this->editor())->tool(SearchArticles::class, ['query' => 'refund'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json->where('count', 0)->etc());
    }

    public function test_search_ranks_title_matches_first_and_respects_the_limit(): void
    {
        $this->article('Billing basics', 'Your invoice arrives monthly.');
        $this->article('Download an invoice', 'Open Billing.');
        $this->article('Invoice languages', 'Pick a language.');

        HelpCenterServer::tool(SearchArticles::class, ['query' => 'invoice', 'limit' => 2])
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('count', 2)
                ->where('articles.0.title', 'Download an invoice')
                ->where('articles.1.title', 'Invoice languages')
                ->etc());
    }

    public function test_search_filters_by_category_slug_or_name(): void
    {
        $this->article('Refunds for teams', 'x', $this->category('Teams'));
        $this->article('Refunds for you', 'x', $this->category('Billing'));

        foreach (['billing', 'Billing'] as $category) {
            HelpCenterServer::tool(SearchArticles::class, ['query' => 'refunds', 'category' => $category])
                ->assertStructuredContent(fn (AssertableJson $json) => $json
                    ->where('count', 1)
                    ->where('articles.0.slug', 'refunds-for-you')
                    ->etc());
        }

        HelpCenterServer::tool(SearchArticles::class, ['query' => 'refunds', 'category' => 'nope'])
            ->assertHasErrors(['There is no category “nope”']);
    }

    public function test_search_validates_its_input(): void
    {
        HelpCenterServer::tool(SearchArticles::class, [])->assertHasErrors(['query']);
        HelpCenterServer::tool(SearchArticles::class, ['query' => 'x', 'limit' => 50])->assertHasErrors(['limit']);
    }

    public function test_search_treats_like_wildcards_as_text(): void
    {
        $this->article('Discounts', 'Save 100% on nothing.');
        $this->article('Plans', 'Nothing here.');

        HelpCenterServer::tool(SearchArticles::class, ['query' => '%'])
            ->assertStructuredContent(fn (AssertableJson $json) => $json->where('count', 1)->where('articles.0.slug', 'discounts')->etc());
    }

    public function test_read_returns_markdown_title_category_url_and_dates(): void
    {
        $article = $this->article('Download an invoice', "Open **Billing**.\n\n## Older invoices\n\nAsk us.", $this->category('Billing'));

        HelpCenterServer::tool(ReadArticle::class, ['slug' => 'download-an-invoice'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('title', 'Download an invoice')
                ->where('slug', 'download-an-invoice')
                ->where('status', 'published')
                ->where('version', 1)
                ->where('category', ['name' => 'Billing', 'slug' => 'billing'])
                ->where('url', route('help.articles.show', 'download-an-invoice'))
                ->where('excerpt', 'Open Billing. Older invoices Ask us.')
                ->where('markdown', "Open **Billing**.\n\n## Older invoices\n\nAsk us.\n")
                ->where('updated_at', $article->updated_at->toIso8601String()));
    }

    public function test_read_accepts_a_title_and_reports_unknown_slugs(): void
    {
        $this->article('Download an invoice');

        HelpCenterServer::tool(ReadArticle::class, ['slug' => 'Download an invoice'])->assertOk()->assertSee('download-an-invoice');
        HelpCenterServer::tool(ReadArticle::class, ['slug' => 'nope'])->assertHasErrors(['There is no published article “nope”']);
        HelpCenterServer::tool(ReadArticle::class, [])->assertHasErrors(['slug']);
    }

    public function test_drafts_are_hidden_from_guests_and_readers_but_not_editors(): void
    {
        $this->article('Secret launch', 'Coming soon.', status: Article::DRAFT);

        HelpCenterServer::tool(ReadArticle::class, ['slug' => 'secret-launch'])
            ->assertHasErrors(['There is no published article'])
            ->assertDontSee('Coming soon');

        HelpCenterServer::actingAs($this->user('Reader'))->tool(ReadArticle::class, ['slug' => 'secret-launch'])
            ->assertHasErrors(['There is no published article']);

        HelpCenterServer::actingAs($this->editor())->tool(ReadArticle::class, ['slug' => 'secret-launch'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('status', 'draft')
                ->where('url', null)
                ->where('markdown', "Coming soon.\n")
                ->etc());
    }

    public function test_categories_list_in_order_with_published_counts(): void
    {
        $billing = $this->category('Billing', '🧾');
        $this->category('Account', null);
        $this->article('Refunds', 'x', $billing);
        $this->article('Draft', 'x', $billing, Article::DRAFT);

        HelpCenterServer::tool(ListCategories::class)
            ->assertOk()
            ->assertStructuredContent(['categories' => [
                [
                    'name' => 'Billing',
                    'slug' => 'billing',
                    'icon' => '🧾',
                    'description' => 'All about Billing.',
                    'url' => route('help.categories.show', 'billing'),
                    'article_count' => 1,
                ],
                [
                    'name' => 'Account',
                    'slug' => 'account',
                    'icon' => null,
                    'description' => 'All about Account.',
                    'url' => route('help.categories.show', 'account'),
                    'article_count' => 0,
                ],
            ]]);
    }

    public function test_an_empty_help_center_says_so(): void
    {
        HelpCenterServer::tool(ListCategories::class)->assertOk()->assertSee('no categories yet');
    }

    public function test_urls_are_null_when_the_host_app_serves_its_own_routes(): void
    {
        config(['help-center.routes' => false]);
        $this->app['router']->setRoutes(new RouteCollection);
        $this->article('Refunds', 'x', $this->category('Billing'));

        HelpCenterServer::tool(ReadArticle::class, ['slug' => 'refunds'])
            ->assertStructuredContent(fn (AssertableJson $json) => $json->where('url', null)->etc());
        HelpCenterServer::tool(ListCategories::class)
            ->assertStructuredContent(fn (AssertableJson $json) => $json->where('categories.0.url', null)->etc());
    }

    public function test_every_article_is_a_markdown_resource(): void
    {
        $this->article('Download an invoice', 'Open Billing.', $this->category('Billing'));
        $this->article('Secret launch', 'Coming soon.', status: Article::DRAFT);

        HelpCenterServer::resource(ArticleResource::class, ['slug' => 'download-an-invoice'])
            ->assertOk()
            ->assertSee(['# Download an invoice', 'Billing · Updated', route('help.articles.show', 'download-an-invoice'), 'Open Billing.']);

        HelpCenterServer::resource(ArticleResource::class, ['slug' => 'secret-launch'])
            ->assertHasErrors(['There is no published article “secret-launch”'])
            ->assertDontSee('Coming soon');

        HelpCenterServer::actingAs($this->editor())->resource(ArticleResource::class, ['slug' => 'secret-launch'])
            ->assertOk()
            ->assertSee(['Draft', 'Coming soon.']);
    }

    public function test_the_server_lists_its_read_tools_and_resource_template(): void
    {
        HelpCenterServer::tools()
            ->assertRegistered([SearchArticles::class, ReadArticle::class, ListCategories::class]);

        $tool = new SearchArticles;
        $this->assertSame('search_articles', $tool->name());
        $this->assertSame(['readOnlyHint' => true, 'idempotentHint' => true], $tool->annotations());
        $this->assertSame('help-center://articles/{slug}', (string) (new ArticleResource)->uriTemplate());
    }
}
