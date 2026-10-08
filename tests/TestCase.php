<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\HelpCenterServiceProvider;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;
use Ruvelo\HelpCenter\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [HelpCenterServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('h', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_editor')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        HelpCenter::flush();

        parent::tearDown();
    }

    protected function user(string $name = 'Ada', bool $editor = false): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => strtolower($name).'@example.com',
            'password' => 'secret',
            'is_editor' => $editor,
        ]);
    }

    /**
     * Defines the gate the way a host app would, and returns an editor.
     */
    protected function editor(string $name = 'Maya'): User
    {
        Gate::define('help-center-edit', fn (User $user) => (bool) $user->is_editor);

        return $this->user($name, editor: true);
    }

    protected function category(string $name = 'Billing', ?string $icon = '💳'): Category
    {
        return (new Category)->commit(['name' => $name, 'icon' => $icon, 'description' => "All about {$name}."]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function article(string $title, string $body = 'Some text.', ?Category $category = null, string $status = Article::PUBLISHED, array $attributes = []): Article
    {
        return (new Article)->commit([
            'title' => $title,
            'body' => $body,
            'status' => $status,
            'category_id' => $category?->id,
            ...$attributes,
        ]);
    }
}
