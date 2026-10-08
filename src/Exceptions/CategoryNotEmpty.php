<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

use Ruvelo\HelpCenter\Models\Category;

final class CategoryNotEmpty extends HelpCenterException
{
    public function __construct(public readonly Category $category, public readonly int $articles)
    {
        parent::__construct("“{$category->name}” still has {$articles} ".($articles === 1 ? 'article' : 'articles').'. Move or delete them first.');
    }
}
