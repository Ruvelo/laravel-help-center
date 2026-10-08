# Contributing to Laravel Help Center

Thanks for helping! Bug reports, docs fixes and features are all welcome.

## Before you start

- **Bugs:** [open an issue](https://github.com/Ruvelo/laravel-help-center/issues/new/choose) with the steps to reproduce, or go straight to a pull request with a failing test.
- **Features:** open an issue first if it's more than a small change, so we can agree on the shape before you spend time on it.
- **Security issues:** don't open an issue. See [SECURITY.md](https://github.com/Ruvelo/.github/blob/main/SECURITY.md).

## Setup

You need PHP 8.3+ and Composer. No database server: tests run on in-memory SQLite.

```bash
git clone https://github.com/<you>/laravel-help-center && cd laravel-help-center
composer install
composer check
```

`composer check` runs exactly what CI runs:

| Command | What it does |
|---|---|
| `composer test` | PHPUnit, through Orchestra Testbench |
| `composer lint` | Code style check (Laravel Pint) |
| `composer format` | Fix code style |
| `composer analyse` | Static analysis (PHPStan level 8 with Larastan) |
| `composer demo` | Build the static demo into `build/` |

## Making a change

1. Branch from `main`.
2. Write a test that fails without your change. Feature tests live in `tests/Feature` (MCP ones in `tests/Feature/Mcp`), unit tests in `tests/Unit`. Code under `src/Mcp` must only load when `laravel/mcp` is installed; the package has to keep working without it.
3. Keep the public API stable: `Ruvelo\HelpCenter\HelpCenter`, the models, events, exceptions, config keys, routes, the Blade component, the JSON shapes and the MCP tool names and arguments. If you must change one, say so in the PR.
4. Run `composer format` and `composer check`.
5. Add a line under **Unreleased** in [CHANGELOG.md](CHANGELOG.md).
6. Open the pull request. Screenshots help for anything visual.

## Where things live

```
src/HelpCenter.php           The public PHP API
src/Models/                  Article, Category, Feedback; commit() is the one write path
src/Markdown/                Rendering and [[article link]] parsing
src/Http/Controllers/        The public site, Manage/ for the editor, Api/ for the JSON API
src/View/Components/         <x-help::articles>, for contextual help in the host app
src/Console/                 help:import and help:export
src/Mcp/                     The optional MCP server and its tools (needs laravel/mcp, a dev dependency here)
resources/boost/guidelines/  Laravel Boost guidelines for coding agents; update them when the API changes
resources/views/             Blade views; the styles live in partials/styles.blade.php
demo/                        The Halyard demo (deployed to GitHub Pages), its build script and screenshots
```

## Style

- `declare(strict_types=1)` everywhere, typed properties and return types.
- No `@phpstan-ignore` or baseline entries: fix the cause.
- Comments explain *why*, not *what*.
- Everything works without JavaScript; scripts only enhance. Nothing loads from a CDN.
- UI follows the [Ruvelo house style](https://github.com/Ruvelo/.github/blob/main/BRAND.md): CSS variables, no build step, works without JavaScript, light and dark.

By contributing you agree that your work is released under the [MIT license](LICENSE) and that you'll follow the [code of conduct](https://github.com/Ruvelo/.github/blob/main/CODE_OF_CONDUCT.md).
