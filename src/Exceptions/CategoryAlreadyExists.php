<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

use Ruvelo\HelpCenter\Models\Category;

final class CategoryAlreadyExists extends HelpCenterException
{
    public function __construct(public readonly Category $category)
    {
        parent::__construct("A category with this name already exists: “{$category->name}” ({$category->slug}).");
    }
}
