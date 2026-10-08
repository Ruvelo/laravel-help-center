<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

/**
 * For your own tests: `Article::factory()->published()->create()`,
 * `Article::factory()->draft()->for($category)->create()` or
 * `Article::factory()->titled('Reset your password')->create()`.
 *
 * Articles are published by default, in a new category.
 *
 * @extends Factory<Article>
 */
final class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(4), '.');

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Article::slugFor($title),
            'body' => "## Overview\n\n".$this->faker->paragraph()."\n\n## Steps\n\n1. ".$this->faker->sentence()."\n2. ".$this->faker->sentence()."\n",
            'status' => Article::PUBLISHED,
            'position' => $this->faker->numberBetween(1, 100),
            'version' => 1,
            'published_at' => now(),
        ];
    }

    public function titled(string $title): self
    {
        return $this->state(['title' => $title, 'slug' => Article::slugFor($title)]);
    }

    public function published(): self
    {
        return $this->state(['status' => Article::PUBLISHED, 'published_at' => now()]);
    }

    public function draft(): self
    {
        return $this->state(['status' => Article::DRAFT, 'published_at' => null]);
    }

    public function uncategorised(): self
    {
        return $this->state(['category_id' => null]);
    }
}
