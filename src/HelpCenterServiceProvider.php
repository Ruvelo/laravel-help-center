<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Ruvelo\HelpCenter\Console\ExportCommand;
use Ruvelo\HelpCenter\Console\ImportCommand;
use Ruvelo\HelpCenter\Markdown\Renderer;

class HelpCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/help-center.php', 'help-center');

        $this->app->singleton(Renderer::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'help');

        // <x-help::articles category="billing" :limit="5" />
        Blade::componentNamespace('Ruvelo\\HelpCenter\\View\\Components', 'help');

        View::composer('help::*', function ($view): void {
            $view->with('canEdit', HelpCenter::canEdit());
        });

        RateLimiter::for('help-center-feedback', fn (Request $request) => Limit::perMinute(max(1, (int) config('help-center.feedback.per_minute', 20)))
            ->by('help-feedback|'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        if (config('help-center.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('help-center.api.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        if (config('help-center.run_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([ImportCommand::class, ExportCommand::class]);

            $this->publishes([
                __DIR__.'/../config/help-center.php' => config_path('help-center.php'),
            ], 'help-center-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/help'),
            ], 'help-center-views');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'help-center-migrations');
        }
    }
}
