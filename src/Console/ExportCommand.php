<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Console;

use Illuminate\Console\Command;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Support\FrontMatter;

/**
 * Writes the help center to a folder: one folder per category (with its
 * details in _category.md) and one {slug}.md per article, so it can be
 * versioned, edited elsewhere, and read back by `help:import`.
 */
final class ExportCommand extends Command
{
    protected $signature = 'help:export
        {path : Folder to write to (created if missing)}
        {--force : Overwrite files that already exist}';

    protected $description = 'Export every category and article to a folder of Markdown files';

    private int $written = 0;

    private int $kept = 0;

    public function handle(): int
    {
        // Artisan reuses the command object, e.g. when called from code.
        $this->written = $this->kept = 0;

        $path = $this->argument('path');
        $root = is_string($path) ? rtrim($path, '/') : '';

        if ($root === '' || (! is_dir($root) && ! @mkdir($root, 0777, true) && ! is_dir($root))) {
            $this->components->error("Couldn't create {$root}");

            return self::FAILURE;
        }

        foreach (Category::query()->orderBy('position')->orderBy('name')->get() as $category) {
            $folder = "{$root}/{$category->slug}";
            is_dir($folder) || mkdir($folder, 0777, true);

            $this->put("{$folder}/_category.md", FrontMatter::join(array_filter([
                'name' => $category->name,
                'icon' => $category->icon,
                'position' => (string) $category->position,
            ], fn ($value) => $value !== null), (string) $category->description));
        }

        Article::query()->with('category:id,slug')->orderBy('id')->lazy()->each(function (Article $article) use ($root): void {
            $folder = $article->category === null ? $root : "{$root}/{$article->category->slug}";

            $this->put("{$folder}/{$article->slug}.md", FrontMatter::join(array_filter([
                'title' => $article->title,
                'slug' => $article->slug,
                'status' => $article->status,
                'position' => (string) $article->position,
                'excerpt' => $article->excerpt,
            ], fn ($value) => $value !== null), $article->body));
        });

        $this->components->info("Wrote {$this->written} files to {$root}".($this->kept ? ", kept {$this->kept} existing files." : '.'));

        return self::SUCCESS;
    }

    private function put(string $file, string $contents): void
    {
        if (is_file($file) && ! $this->option('force')) {
            $this->kept++;
            $this->components->twoColumnDetail(basename(dirname($file)).'/'.basename($file), '<fg=yellow>exists, kept (use --force)</>');

            return;
        }

        file_put_contents($file, $contents);
        $this->written++;
    }
}
