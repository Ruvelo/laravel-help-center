<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Who is voting, without asking anyone to sign in: the signed-in user's ID,
 * else a random token kept in the session and a year-long cookie. Only a
 * hash of it is stored next to a vote.
 */
final class Visitor
{
    public const COOKIE = 'help_visitor';

    /**
     * The visitor's ID, if they have one yet. Never creates one.
     */
    public static function find(Request $request): ?string
    {
        if ($request->user() !== null) {
            return 'user:'.$request->user()->getAuthIdentifier();
        }

        $token = $request->cookie(self::COOKIE);
        if (is_string($token) && preg_match('/^[A-Za-z0-9]{40}$/', $token) === 1) {
            return 'guest:'.$token;
        }

        $token = $request->hasSession() ? $request->session()->get('help.visitor') : null;

        return is_string($token) ? 'guest:'.$token : null;
    }

    /**
     * The visitor's ID, giving them one if they have none.
     */
    public static function get(Request $request): string
    {
        $id = self::find($request);

        if ($id !== null) {
            return $id;
        }

        $token = Str::random(40);
        if ($request->hasSession()) {
            $request->session()->put('help.visitor', $token);
        }
        Cookie::queue(self::COOKIE, $token, 60 * 24 * 365);

        return 'guest:'.$token;
    }

    /**
     * Crawlers, link previews and prefetches shouldn't count as readers.
     */
    public static function isBot(Request $request): bool
    {
        if (in_array(strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose'))), ['prefetch', 'preview'], true)) {
            return true;
        }

        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match('/bot|crawl|spider|slurp|fetch|preview|scan|monitor|curl|wget|python|headless|lighthouse|facebookexternalhit|embedly|whatsapp/i', $agent) === 1;
    }
}
