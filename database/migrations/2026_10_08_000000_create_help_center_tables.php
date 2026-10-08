<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('help-center.table_prefix', 'help_');

        Schema::create($prefix.'categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 32)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
        });

        Schema::create($prefix.'articles', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained($prefix.'categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->string('excerpt', 500)->nullable();
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('position')->default(0);
            // Bumped on every save; the editor sends it back to detect conflicts.
            $table->unsignedInteger('version')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status', 'position']);
            $table->index(['status', 'views']);
        });

        Schema::create($prefix.'feedback', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('article_id')->constrained($prefix.'articles')->cascadeOnDelete();
            $table->boolean('helpful');
            $table->string('comment', 1000)->nullable();
            // A hash of the visitor's cookie, or of the signed-in user's ID.
            $table->string('visitor', 64);
            $table->timestamps();

            $table->unique(['article_id', 'visitor']);
            $table->index(['helpful', 'created_at']);
        });
    }

    public function down(): void
    {
        $prefix = config('help-center.table_prefix', 'help_');

        Schema::dropIfExists($prefix.'feedback');
        Schema::dropIfExists($prefix.'articles');
        Schema::dropIfExists($prefix.'categories');
    }
};
