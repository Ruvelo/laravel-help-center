<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Request;
use Ruvelo\HelpCenter\Events\ArticlePublished;
use Ruvelo\HelpCenter\Events\ArticleUpdated;
use Ruvelo\HelpCenter\Mcp\HelpCenterServer;
use Ruvelo\HelpCenter\Mcp\Tools\WriteArticle;
use Ruvelo\HelpCenter\Models\Article;

class WriteToolTest extends McpTestCase
{
    public function test_only_editors_see_the_write_tool(): void
    {
        HelpCenterServer::tools()->assertNotRegistered(WriteArticle::class);
        HelpCenterServer::tool(WriteArticle::class, ['title' => 'x', 'body' => 'y'])->assertNotRegistered();

        HelpCenterServer::actingAs($this->user('Reader'))->tools()->assertNotRegistered(WriteArticle::class);
        HelpCenterServer::actingAs($this->user('Other'))->tool(WriteArticle::class, ['title' => 'x', 'body' => 'y'])->assertNotRegistered();

        HelpCenterServer::actingAs($this->editor())->tools()->assertRegistered(WriteArticle::class);
        $this->assertSame(0, Article::query()->count());
    }

    public function test_an_editor_creates_a_draft_by_default(): void
    {
        Event::fake([ArticleUpdated::class, ArticlePublished::class]);
        $this->category('Billing');

        HelpCenterServer::actingAs($this->editor())->tool(WriteArticle::class, [
            'title' => 'Download an invoice',
            'body' => 'Open **Billing**.',
            'category' => 'billing',
            'excerpt' => 'Every invoice, as a PDF.',
        ])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('saved', true)
                ->where('created', true)
                ->where('slug', 'download-an-invoice')
                ->where('status', 'draft')
                ->where('version', 1)
                ->where('url', null)
                ->where('category', ['name' => 'Billing', 'slug' => 'billing'])
                ->etc());

        $article = Article::query()->sole();
        $this->assertSame("Open **Billing**.\n", $article->body);
        $this->assertSame('Every invoice, as a PDF.', $article->excerpt);
        Event::assertDispatched(ArticleUpdated::class, fn (ArticleUpdated $event) => $event->created);
        Event::assertNotDispatched(ArticlePublished::class);
    }

    public function test_an_editor_can_publish_directly(): void
    {
        Event::fake([ArticlePublished::class]);

        HelpCenterServer::actingAs($this->editor())->tool(WriteArticle::class, ['title' => 'Refunds', 'body' => 'x', 'status' => 'published'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('status', 'published')
                ->where('url', route('help.articles.show', 'refunds'))
                ->etc());

        $this->assertNotNull(Article::query()->sole()->published_at);
        Event::assertDispatched(ArticlePublished::class);
    }

    public function test_an_update_needs_the_version_it_was_based_on(): void
    {
        $this->article('Refunds', 'Old text.');
        $editor = $this->editor();

        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['slug' => 'refunds', 'body' => 'New text.'])
            ->assertHasErrors(['base version']);

        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['slug' => 'refunds', 'base_version' => 1, 'body' => 'New text.', 'title' => 'Refunds and credits'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('created', false)
                ->where('slug', 'refunds')
                ->where('title', 'Refunds and credits')
                ->where('version', 2)
                ->where('status', 'published')
                ->etc());

        $this->assertSame("New text.\n", Article::query()->sole()->body);
    }

    public function test_a_stale_version_is_refused_with_the_current_one(): void
    {
        $article = $this->article('Refunds', 'Old text.');
        $article->commit(['body' => 'Someone else saved.']);

        HelpCenterServer::actingAs($this->editor())->tool(WriteArticle::class, ['slug' => 'refunds', 'base_version' => 1, 'body' => 'Mine.'])
            ->assertHasErrors(['Not saved', 'is now at version 2', 'base_version 2']);

        $this->assertSame("Someone else saved.\n", $article->fresh()?->body);
    }

    public function test_creating_an_existing_title_points_to_an_update(): void
    {
        $this->article('Refunds', 'Old text.');

        HelpCenterServer::actingAs($this->editor())->tool(WriteArticle::class, ['title' => 'refunds', 'body' => 'Mine.'])
            ->assertHasErrors(['already exists', 'slug “refunds”', 'version 1']);

        $this->assertSame("Old text.\n", Article::query()->sole()->body);
    }

    public function test_bad_input_is_explained(): void
    {
        $editor = $this->editor();

        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['body' => 'x'])->assertHasErrors(['title']);
        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['title' => 'x', 'body' => 'y', 'status' => 'live'])->assertHasErrors(['status']);
        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['title' => '!!!', 'body' => 'y'])->assertHasErrors(['at least one letter or number']);
        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['title' => 'x', 'body' => 'y', 'category' => 'nope'])->assertHasErrors(['There is no category “nope”']);
        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['slug' => 'nope', 'base_version' => 1, 'body' => 'y'])->assertHasErrors(['There is no article “nope”']);

        $this->assertSame(0, Article::query()->count());
    }

    public function test_an_empty_category_uncategorises_the_article(): void
    {
        $this->article('Refunds', 'x', $this->category('Billing'));

        HelpCenterServer::actingAs($this->editor())->tool(WriteArticle::class, ['slug' => 'refunds', 'base_version' => 1, 'category' => ''])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json->where('category', null)->etc());
    }

    public function test_handle_checks_permission_again_when_called_directly(): void
    {
        // e.g. when a Laravel AI agent is handed the tool without the server.
        $result = (new WriteArticle)->handle(new Request(['title' => 'Sneaky', 'body' => 'x']));

        $this->assertTrue($result->isError());
        $this->assertSame(0, Article::query()->count());
    }
}
