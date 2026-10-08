<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

final class InvalidTitle extends HelpCenterException
{
    public function __construct(public readonly string $title)
    {
        parent::__construct('A title needs at least one letter or number.');
    }
}
