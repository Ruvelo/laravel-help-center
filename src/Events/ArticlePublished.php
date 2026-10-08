<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\HelpCenter\Models\Article;

/**
 * Fired when an article goes live: created as published, or a draft
 * switched to published. Fired after ArticleUpdated for the same save.
 */
final class ArticlePublished
{
    use Dispatchable;

    public function __construct(public readonly Article $article) {}
}
