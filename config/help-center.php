<?php

declare(strict_types=1);

use Ruvelo\HelpCenter\Mcp\HelpCenterServer;

return [

    /*
    |--------------------------------------------------------------------------
    | Name
    |--------------------------------------------------------------------------
    |
    | Shown in the header, in page titles and in search results.
    |
    */

    'name' => env('HELP_CENTER_NAME', 'Help Center'),

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | The help center is served under `path` (e.g. /help) and is readable by
    | guests: `middleware` wraps every route, so add `auth` to make it
    | private. The editor lives under `{path}/manage` and also needs the
    | `help-center-edit` gate, which lets no one in until you define it.
    | `manage_middleware` is added on top for the editor. Set `routes` to
    | false to register your own.
    |
    */

    'routes' => true,

    'path' => env('HELP_CENTER_PATH', 'help'),

    'domain' => null,

    'middleware' => ['web'],

    'manage_middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | null uses the help center's own page. Set a view name (e.g.
    | 'layouts.app') to render inside your app's layout instead: the help
    | center fills the `section` you name, and pushes its <head> tags (title
    | aside) to a `help-head` stack if your layout has one.
    |
    */

    'layout' => null,

    'section' => 'content',

    /*
    |--------------------------------------------------------------------------
    | Links
    |--------------------------------------------------------------------------
    |
    | The "Still need help?" box under every article and on the home page.
    | Give a `url` (a contact form, a chat page) or an `email`; with neither,
    | the box is hidden. `app` adds a link back to your product in the header.
    |
    */

    'contact' => [
        'label' => 'Contact us',
        'text' => 'Our support team usually answers within one working day.',
        'url' => env('HELP_CENTER_CONTACT_URL'),
        'email' => env('HELP_CENTER_CONTACT_EMAIL'),
    ],

    'app' => [
        'label' => null,
        'url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Home page
    |--------------------------------------------------------------------------
    |
    | The hero's heading and how many popular articles to list (0 hides them).
    |
    */

    'headline' => 'How can we help?',

    'popular' => 6,

    /*
    |--------------------------------------------------------------------------
    | JSON API
    |--------------------------------------------------------------------------
    |
    | Off by default. Reading published content is public (`middleware`);
    | writes also need `write_middleware` and the `help-center-edit` gate.
    | The default write middleware expects Laravel Sanctum.
    |
    */

    'api' => [
        'enabled' => (bool) env('HELP_CENTER_API', false),
        'prefix' => 'api/help',
        'middleware' => ['api'],
        'write_middleware' => ['auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP server
    |--------------------------------------------------------------------------
    |
    | Lets AI agents and support chatbots search and read the help center over
    | the Model Context Protocol. Needs `composer require laravel/mcp`; off by
    | default. Tools: search_articles, read_article, list_categories, plus
    | write_article for editors when `allow_writes` is on.
    |
    | `middleware` guards the endpoint. The default expects a Sanctum token.
    | A public help center may drop the auth (e.g. ['api', 'throttle:60,1']):
    | guests only ever see published articles. Drafts and write_article also
    | need the `help-center-edit` gate. Set `path` to null to skip the HTTP
    | endpoint, and `local` to true to serve over stdio instead:
    | `php artisan mcp:start help-center`.
    |
    */

    'mcp' => [
        'enabled' => (bool) env('HELP_CENTER_MCP', false),
        'path' => 'mcp/help',
        'middleware' => ['api', 'auth:sanctum'],
        'local' => false,
        'allow_writes' => (bool) env('HELP_CENTER_MCP_WRITES', false),
        'server' => HelpCenterServer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feedback
    |--------------------------------------------------------------------------
    |
    | "Was this article helpful?" votes and comments, per visitor per minute.
    |
    */

    'feedback' => [
        'per_minute' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown
    |--------------------------------------------------------------------------
    |
    | Extra CommonMark extensions (class names or instances), and options
    | merged over the defaults. Raw HTML is escaped unless you change
    | `html_input`; only do that if every editor is trusted.
    |
    */

    'markdown' => [
        'extensions' => [],
        'options' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Three tables: {prefix}categories, {prefix}articles and
    | {prefix}feedback. Set `run_migrations` to false if you publish the
    | migration and run it yourself.
    |
    */

    'table_prefix' => 'help_',

    'run_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'per_page' => 20,

];
