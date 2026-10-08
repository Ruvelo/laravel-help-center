<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Tests\TestCase;

class ComponentTest extends TestCase
{
    public function test_it_lists_a_categorys_published_articles_in_order(): void
    {
        $billing = $this->category('Billing & invoices', '🧾');
        $this->article('Second', 'b', $billing, attributes: ['position' => 2]);
        $this->article('First', 'The first one.', $billing, attributes: ['position' => 1]);
        $this->article('Draft', 'd', $billing, Article::DRAFT);
        $this->article('Elsewhere', 'e', $this->category('Other'));

        $html = Blade::render('<x-help::articles category="billing-invoices" :limit="5" />');

        $this->assertStringContainsString('aria-label="Billing &amp; invoices"', $html);
        $this->assertStringContainsString('🧾', $html);
        $this->assertMatchesRegularExpression('/First.*The first one\..*Second/s', $html);
        $this->assertStringContainsString('href="'.route('help.articles.show', 'first').'"', $html);
        $this->assertStringContainsString('href="'.route('help.categories.show', 'billing-invoices').'"', $html);
        $this->assertStringNotContainsString('Draft', $html);
        $this->assertStringNotContainsString('Elsewhere', $html);
    }

    public function test_it_finds_the_category_by_name_and_respects_the_limit(): void
    {
        $billing = $this->category('Billing');
        foreach (range(1, 4) as $i) {
            $this->article("Tip {$i}", 'x', $billing, attributes: ['position' => $i]);
        }

        $html = Blade::render('<x-help::articles category="Billing" :limit="2" />');

        $this->assertSame(2, substr_count($html, 'class="hcw-link"'));
    }

    public function test_it_can_search_instead(): void
    {
        $this->article('Refunds', 'Money back in five days.');
        $this->article('Plans', 'Pick a plan.');

        $html = Blade::render('<x-help::articles search="money" title="Refunds, explained" />');

        $this->assertStringContainsString('Refunds, explained', $html);
        $this->assertStringContainsString('Refunds', $html);
        $this->assertStringNotContainsString('Plans', $html);
        $this->assertStringContainsString(e(route('help.search', ['q' => 'money', 'all' => 1])), $html);
    }

    public function test_an_unknown_category_shows_an_empty_state(): void
    {
        $this->article('Refunds');

        $html = Blade::render('<x-help::articles category="nope" />');

        $this->assertStringContainsString('No articles yet.', $html);
        $this->assertStringNotContainsString('Refunds', $html);
    }

    public function test_styles_are_scoped_and_included_once(): void
    {
        $this->article('Refunds', category: $this->category('Billing'));

        $html = Blade::render('<x-help::articles category="billing" /><x-help::articles category="billing" class="extra" />');

        $this->assertSame(1, substr_count($html, '<style>'));
        $this->assertSame(2, substr_count($html, '<aside class="hcw'));
        $this->assertStringContainsString('class="hcw extra"', $html);
        // Nothing global that could restyle the host page.
        $css = Str::between($html, '<style>', '</style>');
        preg_match_all('/(?:^|})\s*([^{}@]+)\{/', $css, $selectors);
        foreach ($selectors[1] as $selector) {
            foreach (explode(',', $selector) as $part) {
                $this->assertStringStartsWith('.hcw', trim($part));
            }
        }
    }
}
