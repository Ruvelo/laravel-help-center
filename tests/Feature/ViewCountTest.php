<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ruvelo\HelpCenter\Tests\TestCase;

class ViewCountTest extends TestCase
{
    public function test_readers_count_with_one_update_that_leaves_the_updated_date_alone(): void
    {
        Carbon::setTestNow('2026-01-01 10:00:00');
        $article = $this->article('Refunds');
        Carbon::setTestNow('2026-02-01 10:00:00');

        DB::enableQueryLog();
        $this->get('/help/articles/refunds')->assertOk();
        $updates = array_values(array_filter(DB::getQueryLog(), fn (array $query) => str_starts_with(strtolower($query['query']), 'update')));
        DB::disableQueryLog();

        $this->assertCount(1, $updates);
        $this->assertStringContainsString('"views" = "views" + 1', $updates[0]['query']);
        $this->assertStringNotContainsString('updated_at', $updates[0]['query']);

        $this->get('/help/articles/refunds');

        $this->assertSame(2, $article->fresh()?->views);
        $this->assertSame('2026-01-01 10:00:00', $article->fresh()?->updated_at->toDateTimeString());
        Carbon::setTestNow();
    }

    public function test_editors_bots_and_prefetches_do_not_count(): void
    {
        $article = $this->article('Refunds');

        $this->actingAs($this->editor())->get('/help/articles/refunds')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')->get('/help/articles/refunds')->assertOk();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Safari/605', 'Sec-Purpose' => 'prefetch'])->get('/help/articles/refunds')->assertOk();
        $this->withHeaders(['User-Agent' => '', 'Sec-Purpose' => ''])->get('/help/articles/refunds')->assertOk();

        $this->assertSame(0, $article->fresh()?->views);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh) Safari/605'])->get('/help/articles/refunds');
        $this->assertSame(1, $article->fresh()?->views);
    }

    public function test_signed_in_readers_count(): void
    {
        $article = $this->article('Refunds');

        $this->actingAs($this->user())->get('/help/articles/refunds');

        $this->assertSame(1, $article->fresh()?->views);
    }
}
