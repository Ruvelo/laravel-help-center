<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Ruvelo\HelpCenter\Database\Factories\CategoryFactory;
use Ruvelo\HelpCenter\Events\CategoryDeleted;
use Ruvelo\HelpCenter\Events\CategorySaved;
use Ruvelo\HelpCenter\Exceptions\CategoryAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\CategoryNotEmpty;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int|null $published_articles_count
 * @property int|null $articles_count
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    private const EDITABLE = ['name', 'slug', 'icon', 'description', 'position'];

    protected $guarded = ['id'];

    protected $attributes = ['position' => 0];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    public function getTable(): string
    {
        return config('help-center.table_prefix', 'help_').'categories';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->orderBy('position')->orderBy('title');
    }

    /**
     * Create or change the category: the one write path. Pass any of name,
     * icon, description and position (and slug, when creating). Renaming
     * keeps the slug, so links keep working.
     *
     * @param  array<string, mixed>  $changes
     *
     * @throws InvalidTitle
     * @throws CategoryAlreadyExists
     */
    public function commit(array $changes): self
    {
        $changes = array_intersect_key($changes, array_flip(self::EDITABLE));
        $creating = ! $this->exists;

        if (! $creating) {
            unset($changes['slug']);
        }

        if (array_key_exists('name', $changes) || $creating) {
            $name = trim(self::text($changes['name'] ?? $this->name));
            if (Article::slugFor($name) === '') {
                throw new InvalidTitle($name);
            }
            $changes['name'] = $name;
        }

        foreach (['icon', 'description'] as $key) {
            if (array_key_exists($key, $changes)) {
                $value = trim(self::text($changes[$key]));
                $changes[$key] = $value === '' ? null : $value;
            }
        }

        if ($creating) {
            $slug = Article::slugFor(self::text($changes['slug'] ?? $changes['name']));
            $existing = static::query()->where('slug', $slug)->first();
            if ($existing !== null) {
                throw new CategoryAlreadyExists($existing);
            }
            $changes['slug'] = $slug;
            $changes['position'] ??= (int) static::query()->max('position') + 1;
        }

        $this->fill($changes);

        if ($this->isDirty()) {
            $this->save();
            event(new CategorySaved($this, $creating));
        }

        return $this;
    }

    /**
     * Swap places with the previous (-1) or next (+1) category.
     */
    public function move(int $direction): void
    {
        $ordered = static::query()->orderBy('position')->orderBy('name')->get();
        $index = $ordered->search(fn (Category $category) => $category->is($this));
        $target = is_int($index) ? $ordered->get($index + ($direction < 0 ? -1 : 1)) : null;

        if ($target === null) {
            return;
        }

        // Renumber everything once, so ties left by older data can't stick.
        $ordered = $ordered->values();
        $ordered->splice((int) $index, 1);
        $ordered->splice((int) $index + ($direction < 0 ? -1 : 1), 0, [$this]);

        $this->getConnection()->transaction(function () use ($ordered): void {
            foreach ($ordered->values() as $position => $category) {
                /** @var Category $category */
                $category->commit(['position' => $position + 1]);
            }
        });

        $this->refresh();
    }

    /**
     * Delete the category. Only empty categories can be deleted, so no
     * article is ever left without one by accident.
     *
     * @throws CategoryNotEmpty
     */
    public function remove(): void
    {
        $count = $this->articles()->count();

        if ($count > 0) {
            throw new CategoryNotEmpty($this, $count);
        }

        $this->delete();
        event(new CategoryDeleted($this));
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
