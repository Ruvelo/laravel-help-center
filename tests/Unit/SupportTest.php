<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Support\ArticleLinks;
use Ruvelo\HelpCenter\Support\FrontMatter;
use Ruvelo\HelpCenter\Support\Outline;
use Ruvelo\HelpCenter\Support\PlainText;

class SupportTest extends TestCase
{
    public function test_plain_text_strips_markdown(): void
    {
        $markdown = "## Steps\n\n1. Open **Billing** and see [[Plans|our plans]].\n- Read [the docs](https://x.test).\n\n> **Tip:** use `Ctrl+P`.\n\n| A | B |\n|---|---|\n| 1 | 2 |\n\n```bash\nhalyard login\n```";

        $this->assertSame('Steps Open Billing and see our plans. Read the docs. Tip: use Ctrl+P. A B 1 2 halyard login', PlainText::from($markdown));
    }

    public function test_front_matter_round_trips(): void
    {
        $fields = ['title' => 'Download an invoice: "PDF"', 'status' => 'published', 'position' => '3'];
        $markdown = FrontMatter::join($fields, "Body.\n");

        $this->assertSame([$fields, "Body.\n"], FrontMatter::split($markdown));
        $this->assertSame([[], "No front matter.\n"], FrontMatter::split("No front matter.\n"));
    }

    public function test_link_targets_skip_code(): void
    {
        $this->assertSame(['refunds', 'plans'], ArticleLinks::targets("See [[Refunds]], [[Plans|the plans]], [[Refunds#Timing]].\n\n`[[Code]]`\n\n```\n[[Fenced]]\n```"));
        $this->assertSame(['article' => 'Refunds', 'section' => 'Timing'], ArticleLinks::parseTarget(' Refunds # Timing '));
        $this->assertSame('content-how-long', ArticleLinks::sectionId('How long?'));
    }

    public function test_outline_reads_h2_and_h3(): void
    {
        $html = '<h2>Steps<a id="content-steps" href="#content-steps" class="heading-permalink">#</a></h2><h3>On &amp; off<a id="content-on-off" href="#">#</a></h3><h4>Ignored<a id="x"></a></h4>';

        $this->assertSame([
            ['level' => 2, 'id' => 'content-steps', 'text' => 'Steps'],
            ['level' => 3, 'id' => 'content-on-off', 'text' => 'On & off'],
        ], Outline::of($html));
    }

    public function test_search_words_are_unique_longest_first(): void
    {
        $this->assertSame(['invoice', 'pdf'], Article::words('  PDF invoice pdf '));
        $this->assertSame([], Article::words('   '));
    }

    public function test_slugs_keep_unicode_letters(): void
    {
        $this->assertSame('billing-invoices', Article::slugFor('Billing & invoices'));
        $this->assertSame('café-crème', Article::slugFor('Café Crème'));
        $this->assertSame('', Article::slugFor('???'));
    }

    public function test_bodies_normalise_line_endings_and_the_final_newline(): void
    {
        $this->assertSame("a\nb\n", Article::normalizeBody("a\r\nb\r\n\r\n  "));
        $this->assertSame('', Article::normalizeBody("\n\n"));
    }
}
