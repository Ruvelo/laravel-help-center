<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Ruvelo\HelpCenter\Events\ArticleDeleted;
use Ruvelo\HelpCenter\Events\ArticlePublished;
use Ruvelo\HelpCenter\Events\ArticleUpdated;
use Ruvelo\HelpCenter\Events\CategoryDeleted;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Models\Feedback;
use Ruvelo\HelpCenter\Tests\Fixtures\User;
use Ruvelo\HelpCenter\Tests\TestCase;

class EditorTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    private function editorRoutes(): array
    {
        return [
            ['get', '/help/manage'],
            ['get', '/help/manage/articles/new'],
            ['post', '/help/manage/articles'],
            ['post', '/help/manage/preview'],
            ['get', '/help/manage/articles/refunds/edit'],
            ['put', '/help/manage/articles/refunds'],
            ['delete', '/help/manage/articles/refunds'],
            ['get', '/help/manage/categories'],
            ['post', '/help/manage/categories'],
            ['put', '/help/manage/categories/billing'],
            ['post', '/help/manage/categories/billing/move'],
            ['delete', '/help/manage/categories/billing'],
            ['get', '/help/manage/feedback'],
        ];
    }

    public function test_no_one_can_manage_until_the_gate_is_defined(): void
    {
        $this->article('Refunds', category: $this->category());
        $this->actingAs($this->user());

        foreach ($this->editorRoutes() as [$method, $uri]) {
            $this->call(strtoupper($method), $uri)->assertForbidden();
        }

        $this->get('/help')->assertDontSee('Manage');
    }

    public function test_the_gate_decides_who_manages(): void
    {
        $this->article('Refunds', category: $this->category());
        Gate::define('help-center-edit', fn (User $user) => $user->name === 'Maya');

        $this->actingAs($this->user('Tom'))->get('/help/manage')->assertForbidden();
        $this->actingAs($this->user('Maya'))->get('/help/manage')->assertOk();
    }

    public function test_guests_without_a_login_route_get_a_403_never_a_500(): void
    {
        $this->editor();
        $this->article('Refunds', category: $this->category());
        $this->assertFalse(Route::has('login'));

        foreach ($this->editorRoutes() as [$method, $uri]) {
            $this->call(strtoupper($method), $uri)->assertForbidden();
        }

        $this->getJson('/help/manage')->assertUnauthorized();
    }

    public function test_guests_are_sent_to_the_login_page_when_there_is_one(): void
    {
        Route::get('/login', fn () => 'Sign in')->name('login');
        app('router')->getRoutes()->refreshNameLookups();

        $this->get('/help/manage')->assertRedirect('/login');
    }

    public function test_the_article_list_shows_status_category_views_and_helpfulness(): void
    {
        $billing = $this->category('Billing', '🧾');
        $refunds = $this->article('Refunds', 'Text.', $billing);
        $refunds->forceFill(['views' => 1234, 'helpful_count' => 3, 'not_helpful_count' => 1])->save();
        $this->article('Upcoming', 'Text.', null, Article::DRAFT);

        $this->actingAs($this->editor())->get('/help/manage')->assertOk()
            ->assertSee('Refunds')->assertSee('🧾 Billing')->assertSee('Published')
            ->assertSee('1,234')->assertSee('75%')
            ->assertSee('Upcoming')->assertSee('Draft')->assertSee('No votes')
            ->assertSee('Drafts (1)');
    }

    public function test_the_article_list_filters(): void
    {
        $billing = $this->category('Billing');
        $this->article('Refunds', 'Money back.', $billing);
        $this->article('Upcoming plan', 'Text.', $billing, Article::DRAFT);
        $this->article('Loose ends', 'Text.');
        $this->actingAs($this->editor());

        $rows = fn (string $query) => Str::between((string) $this->get('/help/manage?'.$query)->getContent(), '<tbody>', '</tbody>');

        $this->assertStringNotContainsString('Refunds', $rows('status=draft'));
        $this->assertStringContainsString('Upcoming plan', $rows('status=draft'));
        $this->assertStringContainsString('Loose ends', $rows('category=none'));
        $this->assertStringNotContainsString('Loose ends', $rows('category=billing'));
        $this->assertStringContainsString('Refunds', $rows('q=money'));
        $this->assertStringNotContainsString('Upcoming plan', $rows('q=money'));
        $this->get('/help/manage?q=nothing-like-this')->assertSee('No articles match these filters.');
    }

    public function test_an_empty_list_invites_the_first_article(): void
    {
        $this->actingAs($this->editor())->get('/help/manage')->assertSee('No articles yet.')->assertSee('Write the first one.');
    }

    public function test_editors_create_articles(): void
    {
        Event::fake([ArticleUpdated::class, ArticlePublished::class]);
        $billing = $this->category('Billing');
        $this->actingAs($this->editor());

        $this->get('/help/manage/articles/new?title=Refunds&category=billing')->assertOk()
            ->assertSee('value="Refunds"', false)
            ->assertSee('<option value="'.$billing->id.'" selected', false);

        $this->post('/help/manage/articles', [
            'title' => 'Refunds',
            'body' => "Money back.\r\n",
            'status' => 'draft',
            'category_id' => $billing->id,
            'excerpt' => '',
        ])->assertRedirect(route('help.manage.articles.edit', 'refunds'))->assertSessionHas('help.status', 'Draft saved.');

        $article = Article::query()->where('slug', 'refunds')->sole();
        $this->assertSame("Money back.\n", $article->body);
        $this->assertNull($article->excerpt);
        $this->assertSame(1, $article->position);
        Event::assertDispatched(ArticleUpdated::class, fn (ArticleUpdated $event) => $event->created);
        Event::assertNotDispatched(ArticlePublished::class);

        $this->post('/help/manage/articles', ['title' => 'refunds!', 'status' => 'draft'])
            ->assertSessionHasErrors(['title' => 'An article with this title already exists.']);
        $this->post('/help/manage/articles', ['title' => '???', 'status' => 'draft'])->assertSessionHasErrors('title');
        $this->post('/help/manage/articles', ['title' => 'X', 'status' => 'live'])->assertSessionHasErrors('status');
        $this->post('/help/manage/articles', ['title' => 'X', 'status' => 'draft', 'category_id' => 999])->assertSessionHasErrors('category_id');
    }

    public function test_publishing_a_draft_fires_article_published_once(): void
    {
        $article = $this->article('Refunds', 'Text.', null, Article::DRAFT);
        $this->actingAs($this->editor());
        Event::fake([ArticlePublished::class]);

        $this->put('/help/manage/articles/refunds', ['title' => 'Refunds', 'body' => 'Text.', 'status' => 'published', 'version' => $article->version])
            ->assertSessionHas('help.status', 'Article published.');
        $this->put('/help/manage/articles/refunds', ['title' => 'Refunds', 'body' => 'Better text.', 'status' => 'published', 'version' => $article->fresh()?->version])
            ->assertSessionHas('help.status', 'Changes saved.');

        Event::assertDispatchedTimes(ArticlePublished::class, 1);
        $this->assertNotNull($article->fresh()?->published_at);

        $this->put('/help/manage/articles/refunds', ['title' => 'Refunds', 'body' => 'Better text.', 'status' => 'published', 'version' => $article->fresh()?->version])
            ->assertSessionHas('help.status', 'No changes to save.');
        $this->put('/help/manage/articles/refunds', ['title' => 'Refunds', 'body' => 'Better text.', 'status' => 'draft', 'version' => $article->fresh()?->version])
            ->assertSessionHas('help.status', 'Article unpublished: it is a draft again.');
    }

    public function test_the_editor_has_a_live_preview_and_a_no_javascript_preview(): void
    {
        $article = $this->article('Refunds', "## Timing\n\nFive days.");
        $this->actingAs($this->editor());

        $this->get('/help/manage/articles/refunds/edit')->assertOk()
            ->assertSee('name="version" value="'.$article->version.'"', false)
            ->assertSee('<h2>Timing', false)
            ->assertSee(json_encode(route('help.manage.preview')), false);

        $this->post('/help/manage/preview', ['body' => '**Bold** and [[Refunds]]'])->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('<strong>Bold</strong>', false)
            ->assertSee('href="'.route('help.articles.show', 'refunds').'"', false);

        $this->put('/help/manage/articles/refunds', ['title' => 'Refunds', 'body' => '*Unsaved*', 'status' => 'published', 'version' => $article->version, 'action' => 'preview'])
            ->assertOk()->assertSee('<em>Unsaved</em>', false);
        $this->assertSame("## Timing\n\nFive days.\n", $article->fresh()?->body);
    }

    public function test_a_save_based_on_an_old_version_is_refused_and_keeps_the_text(): void
    {
        $article = $this->article('Refunds', 'Original.');
        $this->actingAs($this->editor());
        $loaded = $article->version;

        // Someone else saves first.
        $article->commit(['body' => 'Their change.']);

        $this->from('/help/manage/articles/refunds/edit')
            ->put('/help/manage/articles/refunds', ['title' => 'Refunds', 'body' => 'My change.', 'status' => 'published', 'version' => $loaded])
            ->assertRedirect('/help/manage/articles/refunds/edit')
            ->assertSessionHasErrors('body')
            ->assertSessionHasInput('body', 'My change.');

        $this->assertSame("Their change.\n", $article->fresh()?->body);
        $this->get('/help/manage/articles/refunds/edit')->assertSee('Someone else saved this article while you were editing it.');
    }

    public function test_renaming_keeps_the_slug(): void
    {
        $article = $this->article('Refunds');
        $this->actingAs($this->editor());

        $this->put('/help/manage/articles/refunds', ['title' => 'Getting your money back', 'body' => 'x', 'status' => 'published', 'version' => $article->version])
            ->assertRedirect(route('help.manage.articles.edit', 'refunds'));

        $this->assertSame('Getting your money back', $article->fresh()?->title);
    }

    public function test_editors_delete_articles(): void
    {
        Event::fake([ArticleDeleted::class]);
        $article = $this->article('Refunds');
        $article->recordFeedback('someone', false, 'Meh');
        $this->actingAs($this->editor());

        $this->delete('/help/manage/articles/refunds')->assertRedirect(route('help.manage.articles.index'))
            ->assertSessionHas('help.status', 'Deleted “Refunds”.');

        $this->assertSame(0, Article::query()->count());
        $this->assertSame(0, Feedback::query()->count());
        Event::assertDispatched(ArticleDeleted::class, fn (ArticleDeleted $event) => $event->article->slug === 'refunds');
    }

    public function test_categories_can_be_created_renamed_reordered_and_deleted_when_empty(): void
    {
        $this->actingAs($this->editor());

        $this->post('/help/manage/categories', ['name' => 'Billing', 'icon' => '🧾', 'description' => 'Invoices.'])
            ->assertSessionHas('help.status', 'Created “Billing”.');
        $this->post('/help/manage/categories', ['name' => 'Payments'])->assertRedirect();
        $this->post('/help/manage/categories', ['name' => 'Account'])->assertRedirect();
        $this->post('/help/manage/categories', ['name' => 'billing'])->assertSessionHasErrors(['name' => 'A category with this name already exists.']);
        $this->post('/help/manage/categories', ['name' => ''])->assertSessionHasErrors('name');

        $order = fn () => Category::query()->orderBy('position')->pluck('name')->all();
        $this->assertSame(['Billing', 'Payments', 'Account'], $order());

        $this->post('/help/manage/categories/account/move', ['direction' => 'up']);
        $this->assertSame(['Billing', 'Account', 'Payments'], $order());
        $this->post('/help/manage/categories/billing/move', ['direction' => 'down']);
        $this->assertSame(['Account', 'Billing', 'Payments'], $order());
        $this->post('/help/manage/categories/account/move', ['direction' => 'up']);
        $this->assertSame(['Account', 'Billing', 'Payments'], $order());

        $this->put('/help/manage/categories/billing', ['name' => 'Billing & invoices', 'icon' => '💳', 'description' => ''])
            ->assertSessionHas('help.status', 'Saved “Billing & invoices”.');
        $billing = Category::query()->where('slug', 'billing')->sole();
        $this->assertSame(['Billing & invoices', '💳', null], [$billing->name, $billing->icon, $billing->description]);

        $this->article('Refunds', category: $billing);
        $this->get('/help/manage/categories')->assertOk()->assertSee('1 article')->assertSee('Move or delete its articles to delete it.');

        Event::fake([CategoryDeleted::class]);
        $this->delete('/help/manage/categories/billing')->assertSessionHas('help.status', '“Billing & invoices” still has 1 article. Move or delete them first.');
        $this->assertTrue($billing->exists());
        $this->delete('/help/manage/categories/payments')->assertSessionHas('help.status', 'Deleted “Payments”.');
        $this->assertSame(['Account', 'Billing & invoices'], $order());
        Event::assertDispatchedTimes(CategoryDeleted::class, 1);
    }

    public function test_the_feedback_page_lists_no_comments_per_article(): void
    {
        $refunds = $this->article('Refunds');
        $plans = $this->article('Plans');
        $refunds->recordFeedback('a', false, 'How long does it take?');
        $refunds->recordFeedback('b', false);
        $refunds->recordFeedback('c', true);
        $plans->recordFeedback('d', false, 'Missing the enterprise plan.');
        $this->actingAs($this->editor());

        $this->get('/help/manage/feedback')->assertOk()
            ->assertSee('Articles readers found least helpful')
            ->assertSeeInOrder(['Refunds', 'Plans'])
            ->assertSee('How long does it take?')
            ->assertSee('Missing the enterprise plan.');

        $this->get('/help/manage/feedback?article=plans')->assertOk()
            ->assertSee('What readers said about “Plans”', false)
            ->assertSee('Missing the enterprise plan.')
            ->assertDontSee('How long does it take?');
    }

    public function test_editor_pages_are_not_indexed(): void
    {
        $this->actingAs($this->editor())->get('/help/manage')->assertSee('<meta name="robots" content="noindex">', false);
    }
}
