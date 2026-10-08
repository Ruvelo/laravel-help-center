<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

final class InvalidStatus extends HelpCenterException
{
    public function __construct(public readonly string $status)
    {
        parent::__construct("Unknown status “{$status}”: use draft or published.");
    }
}
