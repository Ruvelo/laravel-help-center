<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

final class CategoryNotFound extends HelpCenterException
{
    public function __construct(public readonly string $category)
    {
        parent::__construct("No category called “{$category}”. Create it first with HelpCenter::category().");
    }
}
