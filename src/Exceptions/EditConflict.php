<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

use Ruvelo\HelpCenter\Models\Article;

/**
 * The article was saved by someone else after the editor loaded it.
 */
final class EditConflict extends HelpCenterException
{
    public function __construct(
        public readonly Article $article,
        public readonly int $expectedVersion,
        public readonly int $currentVersion,
    ) {
        parent::__construct("“{$article->title}” changed since version {$expectedVersion}; it is now at version {$currentVersion}.");
    }
}
