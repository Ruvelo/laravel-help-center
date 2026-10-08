<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Ruvelo\HelpCenter\Exceptions\HelpCenterException;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Support\FrontMatter;
use SplFileInfo;

/**
 * Imports a folder of Markdown files: a previous `help:export`, a docs
 * folder, or articles moved from another help desk.
 *
 * Each sub-folder is a category (named by an optional _category.md, else
 * by the folder). Front matter can set title, slug, category, status,
 * position and excerpt. Re-importing only saves what changed.
 */
final class ImportCommand extends Command
{
    protected $signature = 'help:import
        {path : A folder of .md files; sub-folders are categories}
        {--status= : Status for files that don\'t set one (draft or published; default draft)}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Import a folder of Markdown files into the help center';

    /** @var array{created: int, updated: int, unchanged: int, skipped: int} */
    private array $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

    public function handle(): int
    {
        $path = $this->argument('path');
        $root = is_string($path) ? rtrim($path, '/') : '';

        if ($root === '' || ! is_dir($root)) {
            $this->components->error("Not a folder: {$root}");

            return self::FAILURE;
        }

        $default = $this->option('status');
        $default = is_string($default) && $default !== '' ? $default : Article::DRAFT;
        if (! in_array($default, Article::STATUSES, true)) {
            $this->components->error("Unknown status “{$default}”: use draft or published.");

            return self::FAILURE;
        }

        foreach ($this->folders($root) as $folder) {
            $category = $folder === $root ? null : $this->category($folder);

            foreach ($this->files($folder) as $file) {
                $this->import($root, $file, $category, $default);
            }
        }

        $this->newLine();
        $this->components->info(sprintf(
            '%s%d created, %d updated, %d unchanged, %d skipped.',
            $this->option('dry-run') ? 'Dry run: ' : '',
            ...array_values($this->counts),
        ));

        return self::SUCCESS;
    }

    private function import(string $root, SplFileInfo $file, ?Category $folderCategory, string $defaultStatus): void
    {
        $relative = ltrim(Str::after($file->getPathname(), $root), '/');
        [$meta, $body] = $this->parse($file);

        $title = $meta['title'];
        if (Article::slugFor($title) === '') {
            $this->skip($relative, 'no usable title');

            return;
        }

        $category = $folderCategory;
        if (($meta['category'] ?? '') !== '') {
            $category = $this->dryRun()
                ? Category::query()->where('slug', Article::slugFor($meta['category']))->first()
                : HelpCenter::category($meta['category']);
        }

        $changes = array_filter([
            'title' => $title,
            'body' => $body,
            'status' => $meta['status'] ?? null,
            'excerpt' => $meta['excerpt'] ?? null,
            'position' => isset($meta['position']) && is_numeric($meta['position']) ? (int) $meta['position'] : null,
        ], fn ($value) => $value !== null);
        $changes['category_id'] = $category?->id;

        if (isset($changes['status']) && ! in_array($changes['status'], Article::STATUSES, true)) {
            $this->skip($relative, "unknown status “{$changes['status']}”");

            return;
        }

        $slug = Article::slugFor($meta['slug'] ?? $title);
        $article = Article::query()->where('slug', $slug)->first();

        if ($article === null) {
            $changes['status'] ??= $defaultStatus;
            $changes['slug'] = $slug;
            $status = 'created';
        } else {
            $status = $article->fill($changes)->isDirty() ? 'updated' : 'unchanged';
            $article->refresh();
        }

        $this->counts[$status]++;

        if ($status !== 'unchanged' && ! $this->dryRun()) {
            try {
                ($article ?? new Article)->commit($changes);
            } catch (HelpCenterException $e) {
                $this->counts[$status]--;
                $this->skip($relative, $e->getMessage());

                return;
            }
        }

        if ($this->output->isVerbose() || $status !== 'unchanged') {
            $this->components->twoColumnDetail($relative, $status);
        }
    }

    private function skip(string $relative, string $reason): void
    {
        $this->counts['skipped']++;
        $this->components->twoColumnDetail($relative, "<fg=yellow>skipped: {$reason}</>");
    }

    private function dryRun(): bool
    {
        return (bool) $this->option('dry-run');
    }

    /**
     * The category for a sub-folder, created (or updated) from its
     * _category.md if it has one.
     */
    private function category(string $folder): ?Category
    {
        $name = Str::ucfirst(str_replace(['-', '_'], ' ', basename($folder)));
        $icon = $description = null;
        $position = null;

        if (is_file($details = "{$folder}/_category.md")) {
            [$meta, $body] = FrontMatter::split(str_replace("\r\n", "\n", (string) file_get_contents($details)));
            $name = ($meta['name'] ?? '') !== '' ? $meta['name'] : $name;
            $icon = $meta['icon'] ?? null;
            $position = isset($meta['position']) && is_numeric($meta['position']) ? (int) $meta['position'] : null;
            $description = trim($body) === '' ? null : trim($body);
        }

        $slug = Article::slugFor(basename($folder));
        $existing = Category::query()->where('slug', $slug)->first()
            ?? Category::query()->where('slug', Article::slugFor($name))->first();

        if ($this->dryRun()) {
            return $existing;
        }

        $changes = array_filter(['name' => $name, 'icon' => $icon, 'description' => $description, 'position' => $position], fn ($value) => $value !== null);

        try {
            return ($existing ?? new Category)->commit($existing === null ? $changes + ['slug' => $slug] : $changes);
        } catch (HelpCenterException $e) {
            $this->components->twoColumnDetail(basename($folder).'/', "<fg=yellow>not a category: {$e->getMessage()}</>");

            return null;
        }
    }

    /**
     * The root and its direct sub-folders, skipping hidden ones.
     *
     * @return list<string>
     */
    private function folders(string $root): array
    {
        $folders = array_values(array_filter(
            glob($root.'/*', GLOB_ONLYDIR) ?: [],
            fn (string $folder) => ! str_starts_with(basename($folder), '.'),
        ));
        sort($folders);

        return [$root, ...$folders];
    }

    /**
     * @return list<SplFileInfo>
     */
    private function files(string $folder): array
    {
        $files = [];
        foreach (glob($folder.'/*') ?: [] as $path) {
            $file = new SplFileInfo($path);
            $name = $file->getFilename();
            if ($file->isFile() && ! str_starts_with($name, '.') && ! str_starts_with($name, '_') && in_array(strtolower($file->getExtension()), ['md', 'markdown'], true)) {
                $files[] = $file;
            }
        }

        usort($files, fn (SplFileInfo $a, SplFileInfo $b) => strcmp($a->getPathname(), $b->getPathname()));

        return $files;
    }

    /**
     * The title comes from front matter, else a leading "# Heading" (which
     * is then dropped from the body, since the article shows its title),
     * else the file name.
     *
     * @return array{array<string, string>&array{title: string}, string}
     */
    private function parse(SplFileInfo $file): array
    {
        $markdown = str_replace("\r\n", "\n", (string) file_get_contents($file->getPathname()));
        [$meta, $body] = FrontMatter::split($markdown);

        $title = trim($meta['title'] ?? '');

        if ($title === '' && preg_match('/\A#\s+(.+)\n*/', $body, $heading) === 1) {
            $title = trim($heading[1]);
            $body = substr($body, strlen($heading[0]));
        }

        if ($title === '') {
            $name = $file->getBasename('.'.$file->getExtension());
            $title = str_contains($name, ' ') ? $name : Str::ucfirst(str_replace(['-', '_'], ' ', $name));
        }

        $meta['title'] = $title;

        return [$meta, Article::normalizeBody($body)];
    }
}
