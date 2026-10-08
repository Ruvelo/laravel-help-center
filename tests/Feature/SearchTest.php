<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Str;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Tests\TestCase;

class SearchTest extends TestCase
{
    public function test_search_finds_every_word_ranks_titles_first_and_highlights_snippets(): void
    {
        $billing = $this->category('Billing');
        $this->article('Payment methods', 'You can pay by card. To download an invoice, open Billing.', $billing);
        $this->article('Download an invoice', 'Open the invoice and press Download.', $billing);
        $this->article('Unrelated', 'Nothing to see.', $billing);

        $response = $this->get('/help/search?q=invoice+download&all=1')->assertOk();

        $response->assertSee('Results for “invoice download”', false)
            ->assertSee('2 articles')
            ->assertSeeInOrder(['<mark>Download</mark> an <mark>invoice</mark>', 'Payment methods'], false)
            ->assertSee('To <mark>download</mark> an <mark>invoice</mark>, open Billing.', false)
            ->assertSee('<span class="hc-chip">Billing</span>', false);

        $this->assertStringNotContainsString('Unrelated', $this->results($response->getContent()));
    }

    public function test_an_exact_title_jumps_to_the_article(): void
    {
        $this->article('Download an invoice', 'Text.');

        $this->get('/help/search?q=download+an+invoice')->assertRedirect(route('help.articles.show', 'download-an-invoice'));
        $this->get('/help/search?q=Download%20an%20Invoice')->assertRedirect(route('help.articles.show', 'download-an-invoice'));
        $this->get('/help/search?q=download+an+invoice&all=1')->assertOk()->assertSee('Results for');
    }

    public function test_drafts_never_show_up_for_readers(): void
    {
        $this->article('Secret pricing', 'Pricing changes next month.', status: Article::DRAFT);

        $this->get('/help/search?q=pricing')->assertOk()->assertSee('No articles match “pricing”', false)->assertDontSee('Secret pricing');
        $this->get('/help/search?q=secret+pricing')->assertOk()->assertDontSee('Secret pricing');
        $this->getJson('/help/search/suggest?q=pricing')->assertExactJson([
            'query' => 'pricing',
            'results' => [],
            'all_url' => route('help.search', ['q' => 'pricing', 'all' => 1]),
        ]);

        $this->actingAs($this->editor())->get('/help/search?q=pricing')->assertSee('Secret <mark>pricing</mark>', false)->assertSee('Draft');
    }

    private function results(string|false $html): string
    {
        return Str::between((string) $html, 'class="hc-results"', '</main>');
    }

    public function test_an_empty_search_asks_for_words(): void
    {
        $this->get('/help/search')->assertOk()->assertSee('Type a question or a few words');
        $this->get('/help/search?q[]=x')->assertOk()->assertSee('Type a question or a few words');
    }

    public function test_like_wildcards_are_matched_literally(): void
    {
        $this->article('Percent', 'Save 100% on fees.');
        $this->article('Underscore', 'Use api_key here.');
        $this->article('Other', 'Nothing 100 here, apixkey.');

        $percent = $this->results($this->get('/help/search?q=100%25&all=1')->getContent());
        $this->assertStringContainsString('Percent', $percent);
        $this->assertStringNotContainsString('Other', $percent);

        $underscore = $this->results($this->get('/help/search?q=api_key&all=1')->getContent());
        $this->assertStringContainsString('Underscore', $underscore);
        $this->assertStringNotContainsString('Other', $underscore);
    }

    public function test_suggestions_come_back_as_json_with_highlights(): void
    {
        $billing = $this->category('Billing & invoices');
        $this->article('Download an invoice', 'Open the invoice and press Download.', $billing);
        $this->article('Refunds', 'Refunds show on your next invoice.', $billing);

        $response = $this->getJson('/help/search/suggest?q=invoice')->assertOk();

        $response->assertJsonPath('query', 'invoice')
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.title', 'Download an invoice')
            ->assertJsonPath('results.0.title_html', 'Download an <mark>invoice</mark>')
            ->assertJsonPath('results.0.url', route('help.articles.show', 'download-an-invoice'))
            ->assertJsonPath('results.0.category', 'Billing & invoices')
            ->assertJsonPath('results.1.snippet_html', 'Refunds show on your next <mark>invoice</mark>.')
            ->assertJsonPath('all_url', route('help.search', ['q' => 'invoice', 'all' => 1]));
    }

    public function test_suggestions_need_two_characters_and_stop_at_six(): void
    {
        foreach (range(1, 8) as $i) {
            $this->article("Invoice tip {$i}");
        }

        $this->getJson('/help/search/suggest?q=i')->assertJsonCount(0, 'results');
        $this->getJson('/help/search/suggest?q=in')->assertJsonCount(6, 'results');
    }

    public function test_suggestions_escape_what_they_highlight(): void
    {
        $this->article('Use <b> tags', 'Write <script>alert(1)</script> nowhere.');

        $json = $this->getJson('/help/search/suggest?q=<b>')->json('results.0');

        $this->assertSame('Use <mark>&lt;b&gt;</mark> tags', $json['title_html']);
        $this->getJson('/help/search/suggest?q=script')->assertJsonPath('results.0.snippet_html', 'Write &lt;<mark>script</mark>&gt;alert(1)&lt;/<mark>script</mark>&gt; nowhere.');
    }

    public function test_the_header_search_keeps_the_query(): void
    {
        $this->article('Refunds', 'Text about money.');

        $this->get('/help/search?q=money')->assertSee('value="money"', false);
    }
}
