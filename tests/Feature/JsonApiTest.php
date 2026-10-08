<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Tests\TestCase;

class JsonApiTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('help-center.api.enabled', true);
        // Sanctum isn't installed here; the session guard stands in for it.
        $app['config']->set('help-center.api.write_middleware', ['auth']);
    }

    public function test_categories_list_with_published_counts(): void
    {
        $billing = $this->category('Billing', '🧾');
        $this->category('Empty', null);
        $this->article('Refunds', 'x', $billing);
        $this->article('Draft', 'x', $billing, Article::DRAFT);

        $this->getJson('/api/help/categories')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0', [
                'name' => 'Billing',
                'slug' => 'billing',
                'icon' => '🧾',
                'description' => 'All about Billing.',
                'position' => 1,
                'url' => route('help.categories.show', 'billing'),
                'article_count' => 1,
            ])
            ->assertJsonPath('data.1.article_count', 0);
    }

    public function test_a_category_with_its_published_articles(): void
    {
        $billing = $this->category('Billing');
        $this->article('Refunds', 'x', $billing);
        $this->article('Draft', 'x', $billing, Article::DRAFT);

        $this->getJson('/api/help/categories/billing')->assertOk()
            ->assertJsonPath('data.slug', 'billing')
            ->assertJsonPath('data.article_count', 1)
            ->assertJsonCount(1, 'data.articles')
            ->assertJsonPath('data.articles.0.slug', 'refunds');

        $this->getJson('/api/help/categories/nope')->assertNotFound();
    }

    public function test_articles_list_published_only_and_filter_by_category(): void
    {
        $billing = $this->category('Billing');
        $this->article('Refunds', 'x', $billing);
        $this->article('Plans', 'x', $this->category('Account'));
        $this->article('Draft', 'x', $billing, Article::DRAFT);

        $this->getJson('/api/help/articles')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/help/articles?category=billing')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Refunds')
            ->assertJsonPath('data.0.category', ['name' => 'Billing', 'slug' => 'billing', 'icon' => '💳'])
            ->assertJsonMissingPath('data.0.body');
        $this->getJson('/api/help/articles?per_page=1')->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 1);
    }

    public function test_one_article_with_body_html_and_outline(): void
    {
        $article = $this->article('Refunds', "Intro.\n\n## Timing\n\nFive days.", attributes: ['excerpt' => 'Money back.']);
        $article->recordFeedback('a', true);
        $this->article('Draft', 'x', status: Article::DRAFT);

        $this->getJson('/api/help/articles/refunds')->assertOk()
            ->assertJsonPath('data.title', 'Refunds')
            ->assertJsonPath('data.excerpt', 'Money back.')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.body', "Intro.\n\n## Timing\n\nFive days.\n")
            ->assertJsonPath('data.outline', [['level' => 2, 'id' => 'content-timing', 'text' => 'Timing']])
            ->assertJsonPath('data.helpful', ['yes' => 1, 'no' => 0])
            ->assertJsonPath('data.url', route('help.articles.show', 'refunds'));

        $this->assertStringContainsString('<h2>Timing', (string) $this->getJson('/api/help/articles/refunds')->json('data.html'));

        $this->getJson('/api/help/articles/draft')->assertNotFound();
        $this->getJson('/api/help/articles/nope')->assertNotFound();
    }

    public function test_search_returns_highlighted_snippets(): void
    {
        $this->article('Refunds', 'Money back in five days.');
        $this->article('Hidden', 'Money draft.', status: Article::DRAFT);

        $this->getJson('/api/help/search?q=money')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'refunds')
            ->assertJsonPath('data.0.snippet_html', '<mark>Money</mark> back in five days.');
    }

    public function test_writes_need_a_signed_in_editor(): void
    {
        $this->category('Billing');
        $this->article('Refunds');

        $this->postJson('/api/help/articles', ['title' => 'New'])->assertUnauthorized();
        $this->patchJson('/api/help/articles/refunds', ['body' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/help/articles/refunds')->assertUnauthorized();
        $this->postJson('/api/help/categories', ['name' => 'X'])->assertUnauthorized();

        $this->actingAs($this->user());
        $this->postJson('/api/help/articles', ['title' => 'New'])->assertForbidden();
        $this->deleteJson('/api/help/categories/billing')->assertForbidden();
    }

    public function test_editors_create_update_and_delete_articles(): void
    {
        $this->category('Billing');
        $this->actingAs($this->editor());

        $this->postJson('/api/help/articles', ['title' => 'Refunds', 'body' => 'Five days.', 'category' => 'billing', 'status' => 'published'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'refunds')
            ->assertJsonPath('data.category.slug', 'billing')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.version', 1);

        $this->postJson('/api/help/articles', ['title' => 'refunds'])->assertStatus(409)->assertJsonPath('article', 'refunds');
        $this->postJson('/api/help/articles', ['title' => '!!!'])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->postJson('/api/help/articles', ['title' => 'X', 'category' => 'nope'])->assertJsonValidationErrors('category');
        $this->postJson('/api/help/articles', ['title' => 'X', 'status' => 'live'])->assertJsonValidationErrors('status');

        $this->patchJson('/api/help/articles/refunds', ['body' => 'Ten days.', 'base_version' => 1])->assertOk()
            ->assertJsonPath('data.body', "Ten days.\n")->assertJsonPath('data.version', 2);

        $this->patchJson('/api/help/articles/refunds', ['body' => 'Stale.', 'base_version' => 1])->assertStatus(409)
            ->assertJsonPath('current_version', 2);
        $this->assertSame("Ten days.\n", Article::query()->where('slug', 'refunds')->value('body'));

        $this->patchJson('/api/help/articles/refunds', ['category' => null, 'status' => 'draft'])->assertOk()
            ->assertJsonPath('data.category', null)->assertJsonPath('data.status', 'draft');

        $this->deleteJson('/api/help/articles/refunds')->assertNoContent();
        $this->assertSame(0, Article::query()->count());
    }

    public function test_editors_manage_categories(): void
    {
        $this->actingAs($this->editor());

        $this->postJson('/api/help/categories', ['name' => 'Billing', 'icon' => '🧾'])->assertCreated()->assertJsonPath('data.slug', 'billing');
        $this->postJson('/api/help/categories', ['name' => 'billing'])->assertStatus(409);
        $this->postJson('/api/help/categories', [])->assertJsonValidationErrors('name');

        $this->patchJson('/api/help/categories/billing', ['name' => 'Billing & invoices', 'description' => 'Plans.'])->assertOk()
            ->assertJsonPath('data.name', 'Billing & invoices')->assertJsonPath('data.slug', 'billing');

        $this->article('Refunds', category: Category::query()->where('slug', 'billing')->sole());
        $this->deleteJson('/api/help/categories/billing')->assertStatus(409)->assertJsonPath('articles', 1);

        Article::query()->delete();
        $this->deleteJson('/api/help/categories/billing')->assertNoContent();
    }
}
