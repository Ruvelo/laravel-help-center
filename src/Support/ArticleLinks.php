<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Support;

use Illuminate\Support\Str;
use Ruvelo\HelpCenter\Models\Article;

final class ArticleLinks
{
    /**
     * [[Article title]], [[Article title|label]], [[Article title#Section]]
     * and [[#Section]], the syntax Obsidian and most wikis share.
     */
    public const PATTERN = '\[\[([^\[\]|\n]+?)(?:\|([^\[\]\n]+?))?\]\]';

    /**
     * Split a link target into its article and section parts.
     *
     * @return array{article: string, section: string|null}
     */
    public static function parseTarget(string $target): array
    {
        $parts = explode('#', $target, 2);
        $section = trim($parts[1] ?? '');

        return ['article' => trim($parts[0]), 'section' => $section === '' ? null : $section];
    }

    /**
     * The id CommonMark gives a heading, so [[Article#Section]] lands on it.
     */
    public static function sectionId(string $section): string
    {
        return 'content-'.Str::slug($section, '-', null);
    }

    /**
     * Slugs of every article the Markdown links to, skipping code.
     *
     * @return list<string>
     */
    public static function targets(string $markdown): array
    {
        $prose = preg_replace(['/^(`{3,}|~{3,}).*?^\1/ms', '/`+[^`\n]*`+/'], '', $markdown) ?? $markdown;

        preg_match_all('/'.self::PATTERN.'/u', $prose, $matches);

        $slugs = array_map(fn (string $target) => Article::slugFor(self::parseTarget($target)['article']), $matches[1]);

        return array_values(array_unique(array_filter($slugs, fn (string $slug) => $slug !== '')));
    }
}
