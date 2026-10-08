<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\HelpCenter\Models\Category;

/**
 * Fired after a category is created, renamed, edited or moved.
 */
final class CategorySaved
{
    use Dispatchable;

    public function __construct(
        public readonly Category $category,
        public readonly bool $created,
    ) {}
}
