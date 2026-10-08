<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Ruvelo\HelpCenter\Database\Factories\ArticleFactory;
use Ruvelo\HelpCenter\Events\ArticleDeleted;
use Ruvelo\HelpCenter\Events\ArticlePublished;
use Ruvelo\HelpCenter\Events\ArticleUpdated;
use Ruvelo\HelpCenter\Events\FeedbackReceived;
use Ruvelo\HelpCenter\Exceptions\ArticleAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\EditConflict;
use Ruvelo\HelpCenter\Exceptions\InvalidStatus;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Markdown\Renderer;
use Ruvelo\HelpCenter\Support\PlainText;

/**
 * @property int $id
 * @property int|null $category_id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string|null $excerpt
 * @property string $status
 * @property int $position
 * @property int $version
 * @property int $views
 * @property int $helpful_count
 * @property int $not_helpful_count
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Category|null $category
 */
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public const STATUSES = [self::DRAFT, self::PUBLISHED];

    /** The attributes commit() accepts. */
    private const EDITABLE = ['title', 'slug', 'body', 'excerpt', 'status', 'position', 'category_id'];

    protected $guarded = ['id'];

    protected $attributes = [
        'body' => '',
        'status' => self::DRAFT,
        'position' => 0,
        'version' => 0,
        'views' => 0,
        'helpful_count' => 0,
        'not_helpful_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'position' => 'integer',
            'version' => 'integer',
            'views' => 'integer',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Don't rely on the database cascading: SQLite only does so with
        // foreign keys switched on.
        static::deleting(fn (Article $article) => $article->feedback()->delete());
        static::deleted(fn (Article $article) => event(new ArticleDeleted($article)));
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    public function getTable(): string
    {
        return config('help-center.table_prefix', 'help_').'articles';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The slug a title (or an [[article link]] target) resolves to. Unicode
     * letters are kept, so non-Latin titles get readable slugs.
     */
    public static function slugFor(string $title): string
    {
        return Str::slug($title, '-', null);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Feedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED);
    }

    /**
     * Published articles, plus drafts for people who can edit.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, ?Authenticatable $user): void
    {
        if (! HelpCenter::canEdit($user)) {
            $query->where('status', self::PUBLISHED);
        }
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('title');
    }

    /**
     * Articles containing every word of the text in their title, excerpt or
     * body. Title matches rank first.
     *
     * @param  Builder<static>  $query
     */
    public function scopeMatching(Builder $query, string $text): void
    {
        $words = self::words($text);

        if ($words === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        foreach ($words as $word) {
            $term = self::likeTerm($word);
            $query->where(fn (Builder $where) => $where
                ->whereRaw("title like ? escape '!'", [$term])
                ->orWhereRaw("excerpt like ? escape '!'", [$term])
                ->orWhereRaw("body like ? escape '!'", [$term]));
        }

        $phrase = self::likeTerm(trim($text));
        $anyInTitle = implode(' or ', array_fill(0, count($words), "title like ? escape '!'"));

        $query->orderByRaw("case when title like ? escape '!' then 0 when {$anyInTitle} then 1 else 2 end", [$phrase, ...array_map(self::likeTerm(...), $words)])
            ->orderByDesc('views')
            ->orderBy('title');
    }

    /**
     * The search words in a query, longest first, without duplicates.
     *
     * @return list<string>
     */
    public static function words(string $text): array
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_unique(array_slice($words, 0, 8)));
        usort($words, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        return $words;
    }

    /**
     * Unix line endings and one final newline, so the same text always
     * compares equal (and re-imports don't count as changes).
     */
    public static function normalizeBody(string $body): string
    {
        $body = rtrim(str_replace(["\r\n", "\r"], "\n", $body));

        return $body === '' ? '' : $body."\n";
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function likeTerm(string $word): string
    {
        // "!" rather than backslash: SQLite has no default escape character
        // and MySQL treats backslash specially, but "!" means the same on all.
        return '%'.strtr($word, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
    }

    public function isPublished(): bool
    {
        return $this->status === self::PUBLISHED;
    }

    public function html(): string
    {
        return app(Renderer::class)->render($this->body);
    }

    /**
     * The body without Markdown syntax, for search snippets and excerpts.
     */
    public function plainText(): string
    {
        return PlainText::from($this->body);
    }

    /**
     * The excerpt, or the start of the body when there is none.
     */
    public function summary(int $limit = 160): string
    {
        $excerpt = trim((string) $this->excerpt);

        return $excerpt !== '' ? $excerpt : Str::limit($this->plainText(), $limit, '…', preserveWords: true);
    }

    /**
     * Share of "Yes" votes, 0–100, or null before the first vote.
     */
    public function helpfulPercent(): ?int
    {
        $total = $this->helpful_count + $this->not_helpful_count;

        return $total === 0 ? null : (int) round($this->helpful_count / $total * 100);
    }

    /**
     * Create or change the article: the one write path. Pass any of title,
     * body, excerpt, status, position and category_id (and slug, when
     * creating). The slug is set once, from the title, so renaming an
     * article keeps its address.
     *
     * Pass the version the editor started from as $basedOn to refuse the
     * save if someone else has saved since. Saving identical content records
     * nothing and fires no events.
     *
     * @param  array<string, mixed>  $changes
     *
     * @throws InvalidTitle
     * @throws InvalidStatus
     * @throws ArticleAlreadyExists
     * @throws EditConflict
     */
    public function commit(array $changes, ?int $basedOn = null): self
    {
        $changes = array_intersect_key($changes, array_flip(self::EDITABLE));
        $creating = ! $this->exists;

        if (! $creating) {
            unset($changes['slug']);
        }

        if (array_key_exists('title', $changes) || $creating) {
            $title = trim(self::text($changes['title'] ?? $this->title));
            if (self::slugFor($title) === '') {
                throw new InvalidTitle($title);
            }
            $changes['title'] = $title;
        }

        if (array_key_exists('status', $changes) && ! in_array($changes['status'], self::STATUSES, true)) {
            throw new InvalidStatus(self::text($changes['status']));
        }

        if (array_key_exists('body', $changes)) {
            $changes['body'] = self::normalizeBody(self::text($changes['body']));
        }

        if (array_key_exists('excerpt', $changes)) {
            $excerpt = trim(self::text($changes['excerpt']));
            $changes['excerpt'] = $excerpt === '' ? null : $excerpt;
        }

        $wasPublished = ! $creating && $this->getOriginal('status') === self::PUBLISHED;

        $saved = $this->getConnection()->transaction(function () use ($changes, $creating, $basedOn): ?array {
            if ($creating) {
                $slug = self::slugFor(self::text($changes['slug'] ?? $changes['title']));
                $existing = static::query()->where('slug', $slug)->first();
                if ($existing !== null) {
                    throw new ArticleAlreadyExists($existing);
                }
                $changes['slug'] = $slug;
            } else {
                // Serialise concurrent saves of the same article, so the
                // conflict check below can't be raced.
                $current = (int) static::query()->whereKey($this->getKey())->lockForUpdate()->value('version');

                if ($basedOn !== null && $basedOn !== $current) {
                    throw new EditConflict($this, $basedOn, $current);
                }
            }

            $this->fill($changes);

            if ($creating && ! array_key_exists('position', $changes)) {
                $this->position = (int) static::query()->where('category_id', $this->category_id)->max('position') + 1;
            }

            if (! $this->isDirty()) {
                return null;
            }

            $changed = array_keys($this->getDirty());

            if ($this->isPublished() && $this->published_at === null) {
                $this->published_at = $this->freshTimestamp();
            }

            $this->version = (int) $this->version + 1;
            $this->save();

            return $changed;
        });

        if ($saved !== null) {
            event(new ArticleUpdated($this, $creating, $saved));

            if ($this->isPublished() && ! $wasPublished) {
                event(new ArticlePublished($this));
            }
        }

        return $this;
    }

    /**
     * Count a view: one indexed UPDATE, without touching updated_at.
     */
    public function recordView(): void
    {
        $this->getConnection()->table($this->getTable())->where('id', $this->id)->increment('views');
        $this->views++;
    }

    /**
     * Record a visitor's vote. Each visitor votes once per article; later
     * votes are ignored, except that a "No" vote can gain a comment once.
     *
     * @param  string  $visitor  a stable, opaque ID for the visitor
     */
    public function recordFeedback(string $visitor, bool $helpful, ?string $comment = null): Feedback
    {
        $comment = trim((string) $comment);
        $comment = $comment === '' ? null : $comment;
        $key = hash('sha256', $visitor);

        $existing = $this->feedback()->where('visitor', $key)->first();

        if ($existing !== null) {
            if ($comment !== null && ! $existing->helpful && $existing->comment === null) {
                $existing->comment = $comment;
                $existing->save();
                event(new FeedbackReceived($existing, commented: true));
            }

            return $existing;
        }

        $column = $helpful ? 'helpful_count' : 'not_helpful_count';

        try {
            $feedback = $this->getConnection()->transaction(function () use ($key, $helpful, $comment, $column): Feedback {
                $feedback = $this->feedback()->create([
                    'visitor' => $key,
                    'helpful' => $helpful,
                    'comment' => $helpful ? null : $comment,
                ]);
                $this->getConnection()->table($this->getTable())->where('id', $this->id)->increment($column);

                return $feedback;
            });
        } catch (UniqueConstraintViolationException) {
            // The same visitor voted twice at once; the other vote won.
            return $this->feedback()->where('visitor', $key)->firstOrFail();
        }

        $this->{$column}++;
        event(new FeedbackReceived($feedback));

        return $feedback;
    }

    /**
     * This visitor's vote on the article, if any.
     */
    public function feedbackFrom(string $visitor): ?Feedback
    {
        return $this->feedback()->where('visitor', hash('sha256', $visitor))->first();
    }
}
