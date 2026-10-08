<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\HelpCenter\Models\Article;

/**
 * Fired after an article and its feedback are deleted. The model is no
 * longer in the database, but its attributes are still readable.
 */
final class ArticleDeleted
{
    use Dispatchable;

    public function __construct(public readonly Article $article) {}
}
