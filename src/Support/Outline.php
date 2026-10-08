<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Support;

final class Outline
{
    /**
     * The h2 and h3 headings of rendered article HTML, for "On this page".
     *
     * @return list<array{level: int, id: string, text: string}>
     */
    public static function of(string $html): array
    {
        preg_match_all('/<h([23])>(.*?)<a id="([^"]+)"/s', $html, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match) => [
            'level' => (int) $match[1],
            'id' => html_entity_decode($match[3], ENT_QUOTES),
            'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES)),
        ], $matches);
    }
}
