<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_the_sitemap_lists_published_articles_and_their_categories(): void
    {
        $billing = $this->category('Billing');
        $drafts = $this->category('Roadmap');
        $article = $this->article('Refunds', 'Text.', $billing);
        $this->article('Secret', 'Text.', $drafts, Article::DRAFT);

        $response = $this->get('/help/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string((string) $response->getContent());
        $this->assertNotFalse($xml);

        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }

        $this->assertSame([
            route('help.home'),
            route('help.categories.show', 'billing'),
            route('help.articles.show', 'refunds'),
        ], $urls);
        $this->assertStringContainsString('<lastmod>'.$article->updated_at->toAtomString().'</lastmod>', (string) $response->getContent());
    }

    public function test_an_empty_help_center_has_a_valid_sitemap(): void
    {
        $xml = simplexml_load_string((string) $this->get('/help/sitemap.xml')->getContent());

        $this->assertNotFalse($xml);
        $this->assertCount(1, $xml->url);
    }
}
