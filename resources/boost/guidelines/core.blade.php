@verbatim
## Ruvelo Help Center (ruvelo/laravel-help-center)

A public help center (FAQ / knowledge base) served at `/help`: categories, Markdown articles, search, "Was this helpful?" feedback and an editor at `/help/manage`. Everything lives under `Ruvelo\HelpCenter`. Read published content through the `HelpCenter` class; change content only through `commit()`.

### Reading content

```php
use Ruvelo\HelpCenter\HelpCenter;

HelpCenter::article('download-an-invoice');               // ?Article, published only (slug or title)
HelpCenter::article('download-an-invoice', drafts: true);  // drafts too: editor-only screens
HelpCenter::search('refund')->limit(5)->get();             // Builder of published matches, best first
HelpCenter::categories();                                  // ordered, with published_articles_count
$article->html();                                          // safe HTML (raw HTML escaped)
$article->summary();                                       // excerpt, or the start of the body
HelpCenter::canEdit($user);                                // the help-center-edit gate
```

Query scopes on `Article`: `published()`, `visibleTo($user)` (adds drafts for editors), `ordered()`, `matching($text)`. Statuses are `Article::DRAFT` and `Article::PUBLISHED`. Links: `route('help.articles.show', $article)`, `route('help.categories.show', $category)`.

### Writing content: one write path

- `HelpCenter::category('Billing & invoices', icon: '🧾', description: '…')` finds or creates a category.
- `HelpCenter::publish($title, $markdown, 'billing')` creates or updates an article by title and publishes it.
- `HelpCenter::write($title, $markdown, 'billing', status: 'draft')` does the same without publishing; new articles are drafts.
- For edits, use `$article->commit([...], basedOn: $version)` with the `version` the user started from. It throws `EditConflict` when someone else saved in between. Show that to the user; never retry blindly.
- `$category->commit([...])`, `$category->move(-1)` and `$category->remove()` (empty categories only) change categories.

```php
$article->commit(['body' => $markdown, 'status' => Article::PUBLISHED], basedOn: $request->integer('version'));
```

Do not call `Article::create()`, `update()`, `save()` or `DB::table('help_articles')` for content changes: they skip validation, versioning and events. Slugs are set once from the title; renaming keeps the URL, so do not edit slugs.

### Events and exceptions

Listen for `ArticleUpdated` (every save; `$created`, `$changed`), `ArticlePublished`, `ArticleDeleted`, `FeedbackReceived`, `CategorySaved` and `CategoryDeleted`, all in `Ruvelo\HelpCenter\Events`. Exceptions extend `Ruvelo\HelpCenter\Exceptions\HelpCenterException`: `InvalidTitle`, `InvalidStatus`, `ArticleAlreadyExists`, `CategoryAlreadyExists`, `CategoryNotFound`, `CategoryNotEmpty` and `EditConflict`.

### Permissions

Anyone can read; no one can edit until the app defines the gate, usually in `AppServiceProvider::boot()`:

```php
Gate::define('help-center-edit', fn ($user) => $user->is_support);
```

### Contextual help in Blade

```blade
<x-help::articles category="billing" :limit="5" />
<x-help::articles search="refund" title="Refunds, explained" />
```

Its styles are self-contained (`hcw-` classes). Don't restyle `.hc`/`.hcw-` from global CSS; override the `--help-*` CSS variables or publish the views (`php artisan vendor:publish --tag=help-center-views`).

### Import, export and seeding

```bash
php artisan help:import docs/help --status=published   # sub-folders are categories; front matter: title, slug, category, status, position, excerpt
php artisan help:import docs/help --dry-run
php artisan help:export storage/help
```

Prefer `help:import` from a folder of Markdown (kept in git) over seeders that write articles one by one.

### Configuration

`config/help-center.php` (publish with `--tag=help-center-config`): `path`, `middleware`, `layout`/`section` (render inside the app's layout), `contact`, `api.*` (JSON API, off by default), `mcp.*` (MCP server for AI agents, off by default; needs laravel/mcp), `table_prefix` (default `help_`), `markdown.*`. Add Markdown extensions with `HelpCenter::extendMarkdown(fn ($environment) => ...)` in a service provider.

### MCP server (optional)

With `laravel/mcp` installed and `HELP_CENTER_MCP=true`, `/mcp/help` serves `search_articles`, `read_article`, `list_categories`, and `write_article` for editors when `mcp.allow_writes` is true. To ground a Laravel AI SDK agent on the help center, return the tool objects from the agent's `tools()`:

```php
use Ruvelo\HelpCenter\Mcp\Tools\{ReadArticle, SearchArticles};

public function tools(): iterable
{
    return [new SearchArticles, new ReadArticle];
}
```

Never hand `WriteArticle` to a customer-facing agent.

### Tests

```php
use Ruvelo\HelpCenter\Models\{Article, Category};

Article::factory()->create();                                   // published, in a new category
Article::factory()->draft()->for(Category::factory())->create();
Article::factory()->titled('Reset your password')->uncategorised()->create();
```

### Don't

- Don't render `$article->body` with `{!! !!}` or your own Markdown parser. Use `$article->html()`.
- Don't show drafts to readers: use `HelpCenter::article()`, `search()` or the `published()` scope in public code.
- Don't add routes under `/help` that clash with the package's (`/help/manage`, `/help/search`, `/help/articles/{slug}`, `/help/categories/{slug}`).
- Don't edit the package's migration; publish it (`--tag=help-center-migrations`) and set `run_migrations` to false if you must change tables.
@endverbatim
