<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Markdown;

use Closure;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Support\ArticleLinks;

/**
 * Turns [[Article]], [[Article|label]] and [[Article#Section]] into links.
 *
 * A link to an article that doesn't exist (or isn't published) shows as
 * plain text to readers, so the public never hits a dead end. Editors see
 * it in red, linking to the form that creates it.
 */
final class ArticleLinkParser implements InlineParserInterface
{
    /**
     * @param  Closure(string): ?string  $resolve  link slug => article slug, if visible
     */
    public function __construct(private readonly Closure $resolve) {}

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex(ArticleLinks::PATTERN);
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        [$target, $label] = array_pad($inlineContext->getSubMatches(), 2, null);

        ['article' => $title, 'section' => $section] = ArticleLinks::parseTarget((string) $target);
        $slug = Article::slugFor($title);
        $fragment = $section === null ? '' : '#'.ArticleLinks::sectionId($section);

        if ($slug === '' && $fragment === '') {
            return false;
        }

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());

        $text = trim((string) $label) ?: trim(implode(' › ', array_filter([$title, $section])));

        if ($slug === '') {
            // [[#Section]]: a link within the current article.
            $link = new Link($fragment, $text);
            $link->data->set('attributes/class', 'help-link');
            $inlineContext->getContainer()->appendChild($link);

            return true;
        }

        $resolved = ($this->resolve)($slug);

        if ($resolved !== null) {
            $link = new Link(route('help.articles.show', $resolved).$fragment, $text);
            $link->data->set('attributes/class', 'help-link');
        } elseif (HelpCenter::canEdit()) {
            $link = new Link(route('help.manage.articles.create', ['title' => $title]), $text);
            $link->data->set('attributes/class', 'help-link help-link--missing');
            $link->data->set('attributes/title', 'No published article yet: write it');
        } else {
            $inlineContext->getContainer()->appendChild(new Text($text));

            return true;
        }

        $inlineContext->getContainer()->appendChild($link);

        return true;
    }
}
