<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\HelpCenter\Models\Category;

final class CategoryDeleted
{
    use Dispatchable;

    public function __construct(public readonly Category $category) {}
}
