<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Facades\View;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_the_home_page_has_a_search_hero_category_cards_and_popular_articles(): void
    {
        $billing = $this->category('Billing & invoices', '🧾');
        $empty = $this->category('Integrations', '🔌');
        $this->article('Download an invoice', 'How to.', $billing)->forceFill(['views' => 40])->save();
        $this->article('Change your plan', 'How to.', $billing)->forceFill(['views' => 10])->save();
        $this->article('Secret roadmap', 'Soon.', $billing, Article::DRAFT);

        $response = $this->get('/help')->assertOk();

        $response->assertSee('How can we help?')
            ->assertSee('name="q"', false)
            ->assertSee('data-help-suggest="'.route('help.search.suggest').'"', false)
            ->assertSee('Billing &amp; invoices', false)
            ->assertSee('🧾')
            ->assertSee('All about Billing &amp; invoices.', false)
            ->assertSee('2 articles')
            ->assertSee('Popular articles')
            ->assertSeeInOrder(['Popular articles', 'Download an invoice', 'Change your plan'])
            ->assertDontSee('Secret roadmap')
            // Categories with nothing published are hidden from readers.
            ->assertDontSee($empty->name);
    }

    public function test_an_empty_help_center_says_so(): void
    {
        $this->get('/help')->assertOk()->assertSee('There are no articles yet.');
    }

    public function test_a_category_lists_its_published_articles_in_order(): void
    {
        $billing = $this->category('Billing');
        $this->article('Second', 'b', $billing, attributes: ['position' => 2]);
        $this->article('First', 'a', $billing, attributes: ['position' => 1, 'excerpt' => 'The very first one.']);
        $this->article('Hidden draft', 'c', $billing, Article::DRAFT);

        $this->get('/help/categories/billing')->assertOk()
            ->assertSee('<title>Billing · Help Center</title>', false)
            ->assertSeeInOrder(['First', 'The very first one.', 'Second'])
            ->assertDontSee('Hidden draft');
    }

    public function test_an_article_shows_in_the_docs_layout(): void
    {
        $billing = $this->category('Billing');
        $this->article('Refunds', 'Refund policy.', $billing);
        $article = $this->article('Download an invoice', "Intro.\n\n## From the dashboard\n\n### On mobile\n\n## By email", $billing);

        $response = $this->get('/help/articles/download-an-invoice')->assertOk();

        $response->assertSee('<h1>Download an invoice</h1>', false)
            ->assertSee('Updated <time datetime="'.$article->updated_at->toIso8601String().'">'.$article->updated_at->format('j F Y').'</time>', false)
            // Breadcrumbs.
            ->assertSeeInOrder(['hc-crumbs', 'Help Center', 'Billing'], false)
            // The menu marks the current article.
            ->assertSee('href="'.route('help.articles.show', 'download-an-invoice').'" aria-current="page"', false)
            // The outline.
            ->assertSeeInOrder(['On this page', 'href="#content-from-the-dashboard"', 'class="is-sub"', 'On mobile', 'By email'], false)
            // Feedback, related, contact.
            ->assertSee('Was this article helpful?')
            ->assertSeeInOrder(['Related articles', 'Refunds'], false);
    }

    public function test_the_contact_box_follows_the_config(): void
    {
        $this->article('Anything');

        $this->get('/help/articles/anything')->assertDontSee('Still need help?');

        config(['help-center.contact.email' => 'support@halyard.test', 'help-center.contact.label' => 'Email support']);
        $this->get('/help/articles/anything')->assertSee('Still need help?')
            ->assertSee('href="mailto:support@halyard.test"', false)->assertSee('Email support');

        config(['help-center.contact.url' => 'https://halyard.test/contact']);
        $this->get('/help/articles/anything')->assertSee('href="https://halyard.test/contact"', false);
    }

    public function test_drafts_are_hidden_from_readers_and_visible_to_editors(): void
    {
        $this->article('Upcoming feature', 'Not yet.', $this->category(), Article::DRAFT);

        $this->get('/help/articles/upcoming-feature')->assertNotFound();
        $this->actingAs($this->user())->get('/help/articles/upcoming-feature')->assertNotFound();

        $this->actingAs($this->editor())->get('/help/articles/upcoming-feature')->assertOk()
            ->assertSee('Draft: only editors can see this')
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertSee(route('help.manage.articles.edit', 'upcoming-feature'));
    }

    public function test_unknown_articles_and_categories_are_404(): void
    {
        $this->get('/help/articles/nope')->assertNotFound();
        $this->get('/help/categories/nope')->assertNotFound();
    }

    public function test_seo_titles_descriptions_and_canonical_urls(): void
    {
        $this->article('Reset your password', 'Forgot it? It happens to everyone. Click the link on the sign-in page.');
        $this->article('Close your account', 'Body.', attributes: ['excerpt' => 'What happens to your data when you leave.']);

        $this->get('/help/articles/reset-your-password?utm_source=mail')
            ->assertSee('<title>Reset your password · Help Center</title>', false)
            ->assertSee('<meta name="description" content="Forgot it? It happens to everyone. Click the link on the sign-in page.">', false)
            ->assertSee('<link rel="canonical" href="'.route('help.articles.show', 'reset-your-password').'">', false);

        $this->get('/help/articles/close-your-account')
            ->assertSee('<meta name="description" content="What happens to your data when you leave.">', false);

        $this->get('/help')->assertSee('<title>Help Center</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('help.home').'">', false);
    }

    public function test_article_links_resolve_and_never_lead_readers_to_a_dead_end(): void
    {
        $this->article('Refunds', 'Policy.');
        $this->article('Draft thing', 'Soon.', status: Article::DRAFT);
        $this->article('Guide', "See [[Refunds]], [[Refunds|our refund policy]], [[Refunds#Timing]], [[Draft thing]] and [[Missing article]].\n\n## Timing\n\nJump to [[#Timing]].");

        $guest = $this->get('/help/articles/guide')->assertOk();
        $guest->assertSee('<a class="help-link" href="'.route('help.articles.show', 'refunds').'">Refunds</a>', false)
            ->assertSee('>our refund policy</a>', false)
            ->assertSee('href="'.route('help.articles.show', 'refunds').'#content-timing"', false)
            ->assertSee('href="#content-timing"', false)
            // Unpublished and missing targets are plain text for readers.
            ->assertSee(' Draft thing and Missing article.', false)
            ->assertDontSee('class="help-link help-link--missing"', false);

        $editor = $this->actingAs($this->editor())->get('/help/articles/guide');
        $editor->assertSee('href="'.route('help.articles.show', 'draft-thing').'"', false)
            ->assertSee('href="'.route('help.manage.articles.create', ['title' => 'Missing article']).'"', false)
            ->assertSee('class="help-link help-link--missing"', false);
    }

    public function test_renaming_keeps_the_address_and_links_to_either_title_work(): void
    {
        $article = $this->article('Old title', 'Text.');
        $article->commit(['title' => 'Shiny new title']);

        $this->assertSame('old-title', $article->slug);
        $this->get('/help/articles/old-title')->assertOk()->assertSee('Shiny new title');

        $html = $this->article('Linker', '[[Old title]] and [[Shiny new title]]')->html();
        $this->assertSame(2, substr_count($html, 'href="'.route('help.articles.show', 'old-title').'"'));
    }

    public function test_it_can_render_inside_the_host_apps_layout(): void
    {
        View::addNamespace('host', __DIR__.'/../Fixtures/views');
        config(['help-center.layout' => 'host::app', 'help-center.section' => 'main']);

        $this->article('Anything', 'Text.');

        $this->get('/help/articles/anything')->assertOk()
            ->assertSee('<title>Host app</title>', false)
            ->assertSee('<nav>Host navigation</nav>', false)
            ->assertSee('<meta name="description" content="Text.">', false)
            ->assertSee('class="hc"', false)
            ->assertSee('<h1>Anything</h1>', false);
    }

    public function test_the_header_links_to_the_app_and_editors_see_manage(): void
    {
        config(['help-center.app' => ['label' => 'Back to Halyard', 'url' => 'https://app.halyard.test']]);

        $this->get('/help')->assertSee('href="https://app.halyard.test"', false)->assertSee('Back to Halyard')
            ->assertDontSee(route('help.manage.articles.index'));

        $this->actingAs($this->editor())->get('/help')->assertSee(route('help.manage.articles.index'));
    }

    public function test_the_path_and_name_are_configurable(): void
    {
        $this->assertSame('http://localhost/help', route('help.home'));
        $this->get('/help')->assertSee('Help Center');

        config(['help-center.name' => 'Halyard Help']);
        $this->get('/help')->assertSee('<title>Halyard Help</title>', false);
    }
}
