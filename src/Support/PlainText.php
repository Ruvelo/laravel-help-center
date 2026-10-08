<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Support;

final class PlainText
{
    /**
     * Markdown without its syntax, for search snippets and excerpts.
     */
    public static function from(string $markdown): string
    {
        $text = preg_replace([
            '/^(`{3,}|~{3,}).*$/mu',                 // code fences (the code stays)
            '/\[\[[^\]|]*\|([^\]]*)\]\]/u',          // [[Target|label]] -> label
            '/\[\[([^\]#]*)(#[^\]]*)?\]\]/u',        // [[Target#Section]] -> Target
            '/!?\[([^\]]*)\]\([^)]*\)/u',            // [text](url) -> text
            '/^\s{0,3}(#{1,6}|>|[-*+]\s+\[[ xX]\]|[-*+]|\d+\.)\s+/mu',
            '/^\s*(\|?\s*:?-{3,}:?\s*)+\|?\s*$/mu',  // table rules
            '/\*{1,3}|`+/u',
            '/\|/u',
        ], ['', '$1', '$1', '$1', '', '', '', ' '], $markdown) ?? $markdown;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
