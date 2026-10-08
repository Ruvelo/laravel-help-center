<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Ruvelo\HelpCenter\Tests\TestCase;

class SecurityTest extends TestCase
{
    private const SCRIPT = '<script>alert("x")</script>';

    public function test_article_bodies_escape_html_and_drop_unsafe_links(): void
    {
        $this->article('Refunds', self::SCRIPT."\n\n<img src=x onerror=alert(1)>\n\n[click](javascript:alert(1)) [[x\" onmouseover=\"alert(1)]]");

        $html = (string) $this->get('/help/articles/refunds')->getContent();

        $this->assertStringNotContainsString(self::SCRIPT, $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringNotContainsString('onmouseover="alert', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_titles_names_and_descriptions_are_escaped_everywhere(): void
    {
        $category = $this->category(self::SCRIPT.' Billing', '<b>');
        $category->commit(['description' => self::SCRIPT]);
        $this->article(self::SCRIPT.' Refunds', 'Text.', $category, attributes: ['excerpt' => self::SCRIPT]);
        $editor = $this->editor();

        foreach (['/help', '/help/categories/script-alertx-script-billing', '/help/articles/script-alertx-script-refunds', '/help/search?q=refunds&all=1', '/help/sitemap.xml'] as $url) {
            $this->assertStringNotContainsString(self::SCRIPT, (string) $this->get($url)->getContent(), $url);
        }

        $this->actingAs($editor);
        foreach (['/help/manage', '/help/manage/categories', '/help/manage/articles/script-alertx-script-refunds/edit'] as $url) {
            $this->assertStringNotContainsString(self::SCRIPT, (string) $this->get($url)->getContent(), $url);
        }

        $this->assertStringNotContainsString(self::SCRIPT, Blade::render('<x-help::articles category="script-alertx-script-billing" />'));
    }

    public function test_search_queries_are_escaped(): void
    {
        $this->article('Refunds', 'Text with <script> in it.');

        $html = (string) $this->get('/help/search?q='.urlencode(self::SCRIPT))->getContent();
        $this->assertStringNotContainsString(self::SCRIPT, $html);

        $html = (string) $this->get('/help/search?q=script&all=1')->getContent();
        $this->assertStringContainsString('&lt;<mark>script</mark>&gt;', $html);
    }

    public function test_feedback_comments_are_escaped_for_editors(): void
    {
        $this->article('Refunds')->recordFeedback('visitor', false, self::SCRIPT);

        $html = (string) $this->actingAs($this->editor())->get('/help/manage/feedback')->getContent();

        $this->assertStringNotContainsString(self::SCRIPT, $html);
        $this->assertStringContainsString(e(self::SCRIPT), $html);
    }

    public function test_writes_need_a_csrf_token_in_a_real_app(): void
    {
        $this->article('Refunds');

        // Testbench skips CSRF checks; the web group carries the middleware.
        $this->assertContains('web', (array) config('help-center.middleware'));
        $this->assertStringContainsString('name="_token"', (string) $this->get('/help/articles/refunds')->getContent());
    }
}
