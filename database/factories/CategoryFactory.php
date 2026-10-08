<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

/**
 * For your own tests: `Category::factory()->create()` or
 * `Category::factory()->named('Billing')->create()`.
 *
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->word().' '.$this->faker->word());

        return [
            'name' => $name,
            'slug' => Article::slugFor($name),
            'icon' => $this->faker->randomElement(['📘', '💳', '🧾', '🔌', '🔐', '🛠️']),
            'description' => $this->faker->sentence(8),
            'position' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function named(string $name): self
    {
        return $this->state(['name' => $name, 'slug' => Article::slugFor($name)]);
    }
}
