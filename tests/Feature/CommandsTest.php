<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Ruvelo\HelpCenter\Events\ArticleUpdated;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Tests\TestCase;

class CommandsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/help-center-test-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function write(string $path, string $contents): void
    {
        $file = $this->dir.'/'.$path;
        is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
        file_put_contents($file, $contents);
    }

    public function test_import_turns_folders_into_categories_and_reads_front_matter(): void
    {
        $this->write('billing/_category.md', "---\nname: Billing & invoices\nicon: 🧾\nposition: 2\n---\n\nPlans, invoices and receipts.\n");
        $this->write('billing/download-an-invoice.md', "---\ntitle: Download an invoice\nstatus: published\nposition: 1\n---\n\nOpen **Billing**.\n");
        $this->write('billing/refunds.md', "# Refunds\n\nFive working days.\n");
        $this->write('getting-started/welcome.md', "---\ntitle: Welcome\ncategory: Billing & invoices\n---\nHi.\n");
        $this->write('loose.md', "Uncategorised.\n");
        $this->write('.hidden/secret.md', "No.\n");
        $this->write('billing/notes.txt', 'Not markdown.');

        $this->artisan('help:import', ['path' => $this->dir])
            ->expectsOutputToContain('4 created, 0 updated, 0 unchanged, 0 skipped.')
            ->assertSuccessful();

        $billing = Category::query()->where('slug', 'billing')->sole();
        $this->assertSame(['Billing & invoices', '🧾', 'Plans, invoices and receipts.', 2], [$billing->name, $billing->icon, $billing->description, $billing->position]);
        $this->assertTrue(Category::query()->where('slug', 'getting-started')->exists());

        $invoice = Article::query()->where('slug', 'download-an-invoice')->sole();
        $this->assertSame(['published', 1, $billing->id, "Open **Billing**.\n"], [$invoice->status, $invoice->position, $invoice->category_id, $invoice->body]);

        $refunds = Article::query()->where('slug', 'refunds')->sole();
        $this->assertSame(['Refunds', 'draft', "Five working days.\n"], [$refunds->title, $refunds->status, $refunds->body]);

        // Front matter beats the folder.
        $this->assertSame($billing->id, Article::query()->where('slug', 'welcome')->value('category_id'));
        $this->assertNull(Article::query()->where('slug', 'loose')->value('category_id'));
        $this->assertFalse(Article::query()->where('slug', 'secret')->exists());
    }

    public function test_reimporting_only_saves_changes(): void
    {
        $this->write('billing/refunds.md', "---\ntitle: Refunds\nstatus: published\n---\n\nFive working days.\n");
        $this->write('billing/plans.md', "---\ntitle: Plans\n---\n\nThree plans.\n");
        $this->artisan('help:import', ['path' => $this->dir])->assertSuccessful();

        Event::fake([ArticleUpdated::class]);
        $this->artisan('help:import', ['path' => $this->dir])
            ->expectsOutputToContain('0 created, 0 updated, 2 unchanged, 0 skipped.')
            ->assertSuccessful();
        Event::assertNotDispatched(ArticleUpdated::class);

        $this->write('billing/plans.md', "---\ntitle: Plans\n---\n\nFour plans now.\n");
        $this->artisan('help:import', ['path' => $this->dir])
            ->expectsOutputToContain('0 created, 1 updated, 1 unchanged, 0 skipped.');

        $this->assertSame("Four plans now.\n", Article::query()->where('slug', 'plans')->value('body'));
        Event::assertDispatchedTimes(ArticleUpdated::class, 1);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->write('billing/refunds.md', "Text.\n");

        $this->artisan('help:import', ['path' => $this->dir, '--dry-run' => true])
            ->expectsOutputToContain('Dry run: 1 created')
            ->assertSuccessful();

        $this->assertSame(0, Article::query()->count());
        $this->assertSame(0, Category::query()->count());
    }

    public function test_import_skips_bad_files_and_rejects_bad_input(): void
    {
        $this->write('bad-status.md', "---\ntitle: Bad\nstatus: live\n---\nx\n");
        $this->write('---.md', "---\ntitle: ???\n---\nx\n");

        $this->artisan('help:import', ['path' => $this->dir])
            ->expectsOutputToContain('0 created, 0 updated, 0 unchanged, 2 skipped.')
            ->assertSuccessful();

        $this->artisan('help:import', ['path' => $this->dir.'/nope'])->assertFailed();
        $this->artisan('help:import', ['path' => $this->dir, '--status' => 'live'])->assertFailed();
    }

    public function test_import_can_publish_everything(): void
    {
        $this->write('billing/refunds.md', "Text.\n");

        $this->artisan('help:import', ['path' => $this->dir, '--status' => 'published'])->assertSuccessful();

        $this->assertTrue(Article::query()->where('slug', 'refunds')->sole()->isPublished());
    }

    public function test_export_and_import_round_trip(): void
    {
        $billing = (new Category)->commit(['name' => 'Billing & invoices', 'icon' => '🧾', 'description' => 'Plans: invoices, receipts.']);
        $empty = (new Category)->commit(['name' => 'Integrations', 'icon' => '🔌']);
        $article = (new Article)->commit(['title' => 'Download an invoice: "PDF" & CSV', 'body' => "## Steps\n\n1. Open [[Billing]].\n", 'status' => 'published', 'category_id' => $billing->id, 'position' => 3, 'excerpt' => 'Get a PDF copy.']);
        $article->commit(['title' => 'Download an invoice, renamed']);
        (new Article)->commit(['title' => 'Loose draft', 'body' => 'Uncategorised.']);

        $this->artisan('help:export', ['path' => $this->dir])->expectsOutputToContain('Wrote 4 files')->assertSuccessful();

        $this->assertFileExists($this->dir.'/billing-invoices/_category.md');
        $this->assertFileExists($this->dir.'/integrations/_category.md');
        $this->assertFileExists($this->dir.'/billing-invoices/download-an-invoice-pdf-csv.md');
        $this->assertFileExists($this->dir.'/loose-draft.md');

        $snapshot = fn () => [
            Category::query()->orderBy('slug')->get(['name', 'slug', 'icon', 'description', 'position'])->toArray(),
            Article::query()->with('category:id,slug')->orderBy('slug')->get()->map(fn (Article $a) => [$a->title, $a->slug, $a->body, $a->status, $a->position, $a->excerpt, $a->category?->slug])->all(),
        ];
        $before = $snapshot();

        // Unchanged: nothing to do.
        $this->artisan('help:import', ['path' => $this->dir])->expectsOutputToContain('0 created, 0 updated, 2 unchanged');

        Article::query()->get()->each->delete();
        Category::query()->delete();

        $this->artisan('help:import', ['path' => $this->dir])->expectsOutputToContain('2 created, 0 updated, 0 unchanged');
        $this->assertEquals($before, $snapshot());
        $this->assertTrue($empty->name === 'Integrations');
    }

    public function test_export_keeps_existing_files_unless_forced(): void
    {
        $this->article('Refunds', 'New text.');
        $this->write('refunds.md', 'Old file.');

        $this->artisan('help:export', ['path' => $this->dir])->expectsOutputToContain('kept 1 existing files.');
        $this->assertSame('Old file.', file_get_contents($this->dir.'/refunds.md'));

        $this->artisan('help:export', ['path' => $this->dir, '--force' => true])->assertSuccessful();
        $this->assertStringContainsString('New text.', (string) file_get_contents($this->dir.'/refunds.md'));
    }
}
