<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Ruvelo\HelpCenter\Http\Controllers\Api\ArticleController;
use Ruvelo\HelpCenter\Http\Controllers\Api\CategoryController;
use Ruvelo\HelpCenter\Http\Middleware\AuthorizeEditing;

// The JSON API. Off by default: set HELP_CENTER_API=true (or help-center.api.enabled).
Route::group([
    'prefix' => config('help-center.api.prefix', 'api/help'),
    'domain' => config('help-center.domain'),
    'middleware' => config('help-center.api.middleware', ['api']),
    'as' => 'help.api.',
], function () {
    // Public: published content only.
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('search', [ArticleController::class, 'search'])->name('search');
    Route::get('articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');

    Route::middleware([...config('help-center.api.write_middleware', ['auth:sanctum']), AuthorizeEditing::class])->group(function () {
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::patch('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });
});
