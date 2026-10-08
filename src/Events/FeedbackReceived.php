<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\HelpCenter\Models\Feedback;

/**
 * Fired when a visitor votes on an article, and again when they add a
 * comment to their "No" vote ($commented is then true).
 */
final class FeedbackReceived
{
    use Dispatchable;

    public function __construct(
        public readonly Feedback $feedback,
        public readonly bool $commented = false,
    ) {}
}
