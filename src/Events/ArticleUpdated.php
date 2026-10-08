<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\HelpCenter\Models\Article;

/**
 * Fired after every save of an article, including the one that creates it.
 */
final class ArticleUpdated
{
    use Dispatchable;

    /**
     * @param  list<string>  $changed  the attributes this save changed
     */
    public function __construct(
        public readonly Article $article,
        public readonly bool $created,
        public readonly array $changed,
    ) {}
}
