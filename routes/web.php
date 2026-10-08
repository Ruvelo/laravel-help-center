<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Ruvelo\HelpCenter\Http\Controllers\ArticleController;
use Ruvelo\HelpCenter\Http\Controllers\CategoryController;
use Ruvelo\HelpCenter\Http\Controllers\HomeController;
use Ruvelo\HelpCenter\Http\Controllers\Manage;
use Ruvelo\HelpCenter\Http\Controllers\SearchController;
use Ruvelo\HelpCenter\Http\Controllers\SitemapController;
use Ruvelo\HelpCenter\Http\Middleware\AuthorizeEditing;

Route::group([
    'prefix' => config('help-center.path', 'help'),
    'domain' => config('help-center.domain'),
    'middleware' => config('help-center.middleware', ['web']),
    'as' => 'help.',
], function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('/categories/{category}', CategoryController::class)->name('categories.show');
    Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    Route::post('/articles/{article}/feedback', [ArticleController::class, 'feedback'])
        ->middleware('throttle:help-center-feedback')->name('articles.feedback');

    Route::prefix('manage')->as('manage.')
        ->middleware([...config('help-center.manage_middleware', []), AuthorizeEditing::class])
        ->group(function () {
            Route::get('/', [Manage\ArticleController::class, 'index'])->name('articles.index');
            Route::get('/articles/new', [Manage\ArticleController::class, 'create'])->name('articles.create');
            Route::post('/articles', [Manage\ArticleController::class, 'store'])->name('articles.store');
            Route::post('/preview', [Manage\ArticleController::class, 'preview'])->name('preview');
            Route::get('/articles/{article}/edit', [Manage\ArticleController::class, 'edit'])->name('articles.edit');
            Route::put('/articles/{article}', [Manage\ArticleController::class, 'update'])->name('articles.update');
            Route::delete('/articles/{article}', [Manage\ArticleController::class, 'destroy'])->name('articles.destroy');

            Route::get('/categories', [Manage\CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [Manage\CategoryController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [Manage\CategoryController::class, 'update'])->name('categories.update');
            Route::post('/categories/{category}/move', [Manage\CategoryController::class, 'move'])->name('categories.move');
            Route::delete('/categories/{category}', [Manage\CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/feedback', Manage\FeedbackController::class)->name('feedback');
        });
});
