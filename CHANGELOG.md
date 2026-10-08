# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-10-08

### Added

- An optional MCP server for AI agents and support chatbots, on top of `laravel/mcp` (suggested, not required). Tools: `search_articles`, `read_article` and `list_categories`, plus `write_article` for editors. Every article is also a `help-center://articles/{slug}` Markdown resource.
- `write_article` saves through `Article::commit()`: drafts by default, versioned, with the usual events, and refused with the current version when someone saved since the agent's `base_version`. It's listed only for users who pass the `help-center-edit` gate, only when `mcp.allow_writes` is on, and checked again on every call.
- Drafts never reach anyone but editors through MCP; search returns published articles only.
- `mcp.*` config: `enabled` (off by default, `HELP_CENTER_MCP`), `path`, `middleware` (`['api', 'auth:sanctum']` by default; may be opened up for a public help center), `local` (stdio through `php artisan mcp:start help-center`), `allow_writes` (off by default) and `server`.
- The MCP tools work as Laravel AI SDK (`laravel/ai`) agent tools too, documented with a support chatbot example.
- Laravel Boost guidelines in `resources/boost/guidelines/core.blade.php`, so coding agents use the `HelpCenter` API, the write path, events, `<x-help::articles>` and the import commands correctly.

## [1.0.0] - 2026-10-08

First release.

### Added

- A public help center at `/help`, readable by guests: a home page with a search hero, category cards and popular articles; category pages; and articles in a docs layout with a category menu, breadcrumbs, "Updated" date and an "On this page" outline.
- Search across titles, excerpts and article text, every word matched, with highlighted snippets; an exact title jumps straight to the article.
- Search suggestions as you type, from a JSON endpoint, with keyboard navigation. Without JavaScript the search box is an ordinary form.
- "Was this article helpful?" with one vote per visitor (session and cookie, or account), an optional comment after "No", and rate limiting. Works without JavaScript.
- Related articles and a configurable "Still need help?" contact box under every article.
- Categories with an emoji icon, description and order; articles with a stable slug, Markdown body, excerpt (or the start of the body), draft and published status, and a position.
- `[[Article links]]`, `[[Article|label]]` and `[[Article#Section]]`, shown as plain text to readers when the target isn't published.
- View counts in one indexed update, skipping editors, bots and prefetches.
- An editor area at `/help/manage`, behind a `help-center-edit` gate that admits no one until defined: the article list with filters, views and helpful share; a side-by-side editor with live preview and edit-conflict protection; category management; and readers' comments.
- SEO: page titles, meta descriptions, canonical URLs, `noindex` where it belongs, and `sitemap.xml`.
- `<x-help::articles>` Blade component for contextual help inside the host app, by category or search.
- `help:import` and `help:export` for folders of Markdown with front matter, round-tripping categories and articles.
- An opt-in JSON API: public reads of published categories, articles and search; writes for editors, with `409` on stale versions and non-empty categories.
- PHP API: `HelpCenter::article()`, `search()`, `categories()`, `category()`, `publish()`, `write()`, `render()` and `extendMarkdown()`.
- Events: `ArticleUpdated`, `ArticlePublished`, `ArticleDeleted`, `FeedbackReceived`, `CategorySaved`, `CategoryDeleted`. Typed exceptions extending `HelpCenterException`.
- `Article::factory()` and `Category::factory()` for tests in host apps.
- Renders standalone or inside the host app's layout (`layout` and `section` config), with styles scoped under `.hc`.
- Ruvelo house style UI: light and dark, no build step, themable through `--help-*` CSS variables.

[Unreleased]: https://github.com/Ruvelo/laravel-help-center/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/Ruvelo/laravel-help-center/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/Ruvelo/laravel-help-center/releases/tag/v1.0.0
