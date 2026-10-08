<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Ruvelo\HelpCenter\Events\FeedbackReceived;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Feedback;
use Ruvelo\HelpCenter\Support\Visitor;
use Ruvelo\HelpCenter\Tests\TestCase;

class FeedbackTest extends TestCase
{
    public function test_the_form_works_without_javascript(): void
    {
        $article = $this->article('Refunds', 'Text.');

        $this->get('/help/articles/refunds')->assertSee('data-state="ask"', false)
            ->assertSee('action="'.route('help.articles.feedback', 'refunds').'"', false)
            ->assertSee('name="helpful" value="1"', false);

        $this->post('/help/articles/refunds/feedback', ['helpful' => '1'])
            ->assertRedirect(route('help.articles.show', 'refunds').'#feedback')
            ->assertSessionHas('help.status', 'Thanks for your feedback.')
            ->assertCookie(Visitor::COOKIE);

        $this->assertSame(1, $article->fresh()?->helpful_count);
        $this->get('/help/articles/refunds')->assertSee('data-state="yes"', false);
    }

    public function test_one_vote_per_visitor(): void
    {
        $article = $this->article('Refunds');

        $this->post('/help/articles/refunds/feedback', ['helpful' => '1']);
        $this->post('/help/articles/refunds/feedback', ['helpful' => '1']);
        $this->post('/help/articles/refunds/feedback', ['helpful' => '0']);

        $this->assertSame([1, 0], [$article->fresh()?->helpful_count, $article->fresh()?->not_helpful_count]);
        $this->assertSame(1, Feedback::query()->count());

        // Someone else (a new session, no cookie) gets their own vote.
        $this->flushSession();
        $this->post('/help/articles/refunds/feedback', ['helpful' => '0']);

        $this->assertSame(1, $article->fresh()?->not_helpful_count);
        $this->assertSame(2, Feedback::query()->count());
    }

    public function test_the_visitor_cookie_outlives_the_session(): void
    {
        $article = $this->article('Refunds');

        $this->post('/help/articles/refunds/feedback', ['helpful' => '1'])->assertCookie(Visitor::COOKIE);
        $token = $this->app['session']->get('help.visitor');

        $this->flushSession();
        $this->withCookie(Visitor::COOKIE, $token)->post('/help/articles/refunds/feedback', ['helpful' => '1']);

        $this->assertSame(1, $article->fresh()?->helpful_count);
    }

    public function test_signed_in_readers_vote_once_whichever_device(): void
    {
        $article = $this->article('Refunds');
        $user = $this->user();

        $this->actingAs($user)->post('/help/articles/refunds/feedback', ['helpful' => '1']);
        $this->flushSession();
        $this->actingAs($user)->post('/help/articles/refunds/feedback', ['helpful' => '0']);

        $this->assertSame([1, 0], [$article->fresh()?->helpful_count, $article->fresh()?->not_helpful_count]);
    }

    public function test_a_no_vote_can_add_one_short_comment(): void
    {
        Event::fake([FeedbackReceived::class]);
        $article = $this->article('Refunds');

        $this->post('/help/articles/refunds/feedback', ['helpful' => '0'])
            ->assertSessionHas('help.status', 'Thanks for telling us. What were you looking for?');
        $this->get('/help/articles/refunds')->assertSee('data-state="no"', false)->assertSee('What were you looking for?');

        $this->post('/help/articles/refunds/feedback', ['helpful' => '0', 'comment' => '  It does not say how long refunds take.  ']);
        $this->post('/help/articles/refunds/feedback', ['helpful' => '0', 'comment' => 'Second try is ignored.']);

        $feedback = Feedback::query()->sole();
        $this->assertFalse($feedback->helpful);
        $this->assertSame('It does not say how long refunds take.', $feedback->comment);
        $this->assertSame(1, $article->fresh()?->not_helpful_count);
        $this->get('/help/articles/refunds')->assertSee('data-state="done"', false);

        Event::assertDispatchedTimes(FeedbackReceived::class, 2);
        Event::assertDispatched(FeedbackReceived::class, fn (FeedbackReceived $event) => $event->commented && $event->feedback->is($feedback));
    }

    public function test_a_no_vote_and_comment_can_arrive_together(): void
    {
        $this->article('Refunds');

        $this->postJson('/help/articles/refunds/feedback', ['helpful' => false, 'comment' => 'Too vague.'])
            ->assertOk()->assertExactJson(['state' => 'done']);

        $this->assertSame('Too vague.', Feedback::query()->sole()->comment);
    }

    public function test_yes_votes_carry_no_comment(): void
    {
        $this->article('Refunds');

        $this->postJson('/help/articles/refunds/feedback', ['helpful' => true, 'comment' => 'Great'])->assertExactJson(['state' => 'yes']);

        $this->assertNull(Feedback::query()->sole()->comment);
    }

    public function test_feedback_is_validated(): void
    {
        $this->article('Refunds');

        $this->postJson('/help/articles/refunds/feedback', [])->assertUnprocessable()->assertJsonValidationErrors('helpful');
        $this->postJson('/help/articles/refunds/feedback', ['helpful' => 'maybe'])->assertJsonValidationErrors('helpful');
        $this->postJson('/help/articles/refunds/feedback', ['helpful' => 0, 'comment' => str_repeat('a', 501)])->assertJsonValidationErrors('comment');
        $this->assertSame(0, Feedback::query()->count());
    }

    public function test_drafts_and_unknown_articles_take_no_votes(): void
    {
        $this->article('Draft', status: Article::DRAFT);

        $this->post('/help/articles/draft/feedback', ['helpful' => '1'])->assertNotFound();
        $this->post('/help/articles/nope/feedback', ['helpful' => '1'])->assertNotFound();
    }

    public function test_voting_does_not_change_the_updated_date(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');
        $article = $this->article('Refunds');
        Carbon::setTestNow('2026-03-01 10:00:00');

        $this->post('/help/articles/refunds/feedback', ['helpful' => '1']);

        $this->assertSame('2026-01-01 10:00:00', $article->fresh()?->updated_at->toDateTimeString());
        Carbon::setTestNow();
    }

    public function test_feedback_is_rate_limited(): void
    {
        config(['help-center.feedback.per_minute' => 2]);
        $this->article('Refunds');

        $this->post('/help/articles/refunds/feedback', ['helpful' => '1'])->assertRedirect();
        $this->post('/help/articles/refunds/feedback', ['helpful' => '1'])->assertRedirect();
        $this->post('/help/articles/refunds/feedback', ['helpful' => '1'])->assertTooManyRequests();
    }

    public function test_the_article_shows_every_state_for_the_script_to_switch_between(): void
    {
        $this->article('Refunds');

        $this->get('/help/articles/refunds')
            ->assertSeeInOrder(['class="hc-fb-ask"', 'class="hc-fb-yes"', 'class="hc-fb-no"', 'class="hc-fb-done"'], false)
            ->assertSee('data-article="refunds"', false);
    }
}
