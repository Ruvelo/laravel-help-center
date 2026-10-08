<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Support;

use Illuminate\Support\Str;
use Ruvelo\HelpCenter\Models\Article;

/**
 * Search snippets: a window of text around the first match, escaped, with
 * every query word wrapped in <mark>.
 */
final class Snippet
{
    public static function around(string $text, string $query, int $radius = 90): string
    {
        foreach (Article::words($query) as $word) {
            $excerpt = Str::excerpt($text, $word, ['radius' => $radius, 'omission' => '…']);
            if (is_string($excerpt) && $excerpt !== '') {
                // Start and end on whole words.
                return preg_replace(['/^…\S*\s+/u', '/\s+\S*…$/u'], ['…', '…'], $excerpt) ?? $excerpt;
            }
        }

        return Str::limit($text, $radius * 2, '…', preserveWords: true);
    }

    /**
     * Escaped HTML with the query's words highlighted.
     */
    public static function highlight(string $text, string $query): string
    {
        $escaped = e($text);
        $words = array_map(fn (string $word) => preg_quote(e($word), '/'), Article::words($query));

        if ($words === []) {
            return $escaped;
        }

        return preg_replace('/'.implode('|', $words).'/iu', '<mark>$0</mark>', $escaped) ?? $escaped;
    }

    /**
     * The article's best snippet for the query, as safe HTML.
     */
    public static function for(Article $article, string $query): string
    {
        $text = $article->plainText();

        foreach (Article::words($query) as $word) {
            if (mb_stripos($text, $word) !== false) {
                return self::highlight(self::around($text, $query), $query);
            }
        }

        return self::highlight($article->summary(180), $query);
    }
}
