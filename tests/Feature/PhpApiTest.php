<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Facades\Event;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Ruvelo\HelpCenter\Events\ArticlePublished;
use Ruvelo\HelpCenter\Events\ArticleUpdated;
use Ruvelo\HelpCenter\Events\CategorySaved;
use Ruvelo\HelpCenter\Exceptions\ArticleAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\CategoryAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\CategoryNotEmpty;
use Ruvelo\HelpCenter\Exceptions\CategoryNotFound;
use Ruvelo\HelpCenter\Exceptions\EditConflict;
use Ruvelo\HelpCenter\Exceptions\HelpCenterException;
use Ruvelo\HelpCenter\Exceptions\InvalidStatus;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Tests\TestCase;

class PhpApiTest extends TestCase
{
    public function test_categories_are_created_found_and_updated(): void
    {
        $billing = HelpCenter::category('Billing & invoices', icon: '🧾', description: 'Plans and invoices.');

        $this->assertSame(['billing-invoices', 1], [$billing->slug, $billing->position]);
        $this->assertTrue(HelpCenter::category('billing-invoices')->is($billing));
        $this->assertTrue(HelpCenter::category('Billing & invoices', icon: '💳')->is($billing));
        $this->assertSame('💳', $billing->fresh()?->icon);
        $this->assertSame('Plans and invoices.', $billing->fresh()?->description);
    }

    public function test_categories_list_in_order_with_published_counts(): void
    {
        $b = HelpCenter::category('B', position: 2);
        HelpCenter::category('A', position: 1);
        HelpCenter::publish('One', 'x', $b);
        HelpCenter::write('Two', 'x', $b);

        $categories = HelpCenter::categories();

        $this->assertSame(['A', 'B'], $categories->pluck('name')->all());
        $this->assertSame([0, 1], $categories->pluck('published_articles_count')->all());
    }

    public function test_write_creates_drafts_and_publish_publishes(): void
    {
        Event::fake([ArticleUpdated::class, ArticlePublished::class]);
        HelpCenter::category('Billing');

        $draft = HelpCenter::write('Refunds', 'Five days.', 'billing');
        $this->assertSame([Article::DRAFT, 'refunds'], [$draft->status, $draft->slug]);
        Event::assertNotDispatched(ArticlePublished::class);

        $published = HelpCenter::publish('Refunds', 'Five days.');
        $this->assertTrue($published->is($draft));
        $this->assertTrue($published->isPublished());
        $this->assertSame('billing', $published->category?->slug);
        Event::assertDispatchedTimes(ArticlePublished::class, 1);

        // Writing the same thing again records nothing.
        $before = $published->version;
        HelpCenter::publish('Refunds', 'Five days.');
        $this->assertSame($before, $published->fresh()?->version);
        Event::assertDispatchedTimes(ArticleUpdated::class, 2);
        Event::assertDispatched(ArticleUpdated::class, fn (ArticleUpdated $event) => ! $event->created && $event->changed === ['status']);
    }

    public function test_write_updates_by_slug_whatever_the_spelling(): void
    {
        $article = HelpCenter::publish('Refunds', 'Old.');

        HelpCenter::write('REFUNDS', 'New.', excerpt: 'Money back.', position: 7);

        $article->refresh();
        $this->assertSame(['REFUNDS', "New.\n", 'Money back.', 7, Article::PUBLISHED], [$article->title, $article->body, $article->excerpt, $article->position, $article->status]);
    }

    public function test_article_finds_published_by_slug_or_title(): void
    {
        HelpCenter::publish('Download an invoice', 'x');
        HelpCenter::write('Secret', 'x');

        $this->assertSame('download-an-invoice', HelpCenter::article('Download an invoice')?->slug);
        $this->assertSame('download-an-invoice', HelpCenter::article('download-an-invoice')?->slug);
        $this->assertNull(HelpCenter::article('secret'));
        $this->assertNotNull(HelpCenter::article('secret', drafts: true));
        $this->assertNull(HelpCenter::article('???'));
    }

    public function test_search_returns_a_query_of_published_matches(): void
    {
        HelpCenter::publish('Refunds', 'Money back.');
        HelpCenter::publish('Money matters', 'Plans.');
        HelpCenter::write('Money draft', 'x');

        $this->assertSame(['Money matters', 'Refunds'], HelpCenter::search('money')->pluck('title')->all());
        $this->assertSame(['Money matters'], HelpCenter::search('money')->limit(1)->pluck('title')->all());
        $this->assertSame([], HelpCenter::search('   ')->pluck('title')->all());
    }

    public function test_render_turns_markdown_into_safe_html(): void
    {
        HelpCenter::publish('Refunds', 'x');

        $html = HelpCenter::render('**Bold**, [[Refunds]] and <script>alert(1)</script>');

        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringContainsString('href="'.route('help.articles.show', 'refunds').'"', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_markdown_can_be_extended(): void
    {
        HelpCenter::extendMarkdown(fn (Environment $environment) => $environment->addRenderer(Strong::class, new class implements NodeRendererInterface
        {
            public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
            {
                return '<b class="loud">'.$childRenderer->renderNodes($node->children()).'</b>';
            }
        }, 10));

        $this->assertStringContainsString('<b class="loud">Hi</b>', HelpCenter::render('**Hi**'));

        HelpCenter::flush();
        $this->assertStringContainsString('<strong>Hi</strong>', HelpCenter::render('**Hi**'));
    }

    public function test_typed_exceptions(): void
    {
        HelpCenter::publish('Refunds', 'x');
        HelpCenter::category('Billing');

        $cases = [
            InvalidTitle::class => fn () => HelpCenter::write('  ', 'x'),
            CategoryNotFound::class => fn () => HelpCenter::write('New', 'x', 'nope'),
            InvalidStatus::class => fn () => HelpCenter::write('New', 'x', status: 'live'),
            ArticleAlreadyExists::class => fn () => (new Article)->commit(['title' => 'Refunds']),
            CategoryAlreadyExists::class => fn () => (new Category)->commit(['name' => 'billing']),
            EditConflict::class => fn () => HelpCenter::article('refunds')?->commit(['body' => 'y'], basedOn: 99),
            CategoryNotEmpty::class => function () {
                HelpCenter::write('In billing', 'x', 'billing');
                Category::query()->where('slug', 'billing')->sole()->remove();
            },
        ];

        foreach ($cases as $class => $call) {
            try {
                $call();
                $this->fail("Expected {$class}");
            } catch (HelpCenterException $e) {
                $this->assertInstanceOf($class, $e);
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_the_conflict_exception_says_which_versions(): void
    {
        $article = HelpCenter::publish('Refunds', 'x');
        $article->commit(['body' => 'y']);

        try {
            $article->commit(['body' => 'z'], basedOn: 1);
            $this->fail('Expected a conflict');
        } catch (EditConflict $e) {
            $this->assertSame([1, 2], [$e->expectedVersion, $e->currentVersion]);
            $this->assertTrue($e->article->is($article));
        }
    }

    public function test_category_events(): void
    {
        Event::fake([CategorySaved::class]);

        $category = HelpCenter::category('Billing');
        $category->commit(['name' => 'Billing']);
        $category->commit(['name' => 'Billing & invoices']);

        Event::assertDispatchedTimes(CategorySaved::class, 2);
        Event::assertDispatched(CategorySaved::class, fn (CategorySaved $event) => $event->created);
    }

    public function test_excerpts_fall_back_to_the_start_of_the_body(): void
    {
        $article = HelpCenter::publish('Refunds', "## Timing\n\nRefunds take **five** working days to reach your [bank](https://example.com). ".str_repeat('More words here. ', 20));

        $this->assertStringStartsWith('Timing Refunds take five working days to reach your bank.', $article->summary());
        $this->assertLessThanOrEqual(161, mb_strlen($article->summary()));
        $this->assertStringEndsWith('…', $article->summary());

        $article->commit(['excerpt' => '  Money back, explained.  ']);
        $this->assertSame('Money back, explained.', $article->summary());
    }

    public function test_the_factories_build_realistic_records(): void
    {
        $article = Article::factory()->create();
        $draft = Article::factory()->draft()->for(Category::factory()->named('Billing'))->create();
        $loose = Article::factory()->uncategorised()->titled('Reset your password')->create();

        $this->assertTrue($article->isPublished());
        $this->assertNotNull($article->category);
        $this->assertFalse($draft->isPublished());
        $this->assertSame('billing', $draft->category?->slug);
        $this->assertSame(['reset-your-password', null], [$loose->slug, $loose->category_id]);
        $this->get(route('help.articles.show', $article))->assertOk();
    }
}
