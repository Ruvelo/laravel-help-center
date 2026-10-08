<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

use Ruvelo\HelpCenter\Models\Article;

final class ArticleAlreadyExists extends HelpCenterException
{
    public function __construct(public readonly Article $article)
    {
        parent::__construct("An article with this title already exists: “{$article->title}” ({$article->slug}).");
    }
}
