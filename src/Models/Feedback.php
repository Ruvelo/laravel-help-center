<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One visitor's answer to "Was this article helpful?".
 *
 * @property int $id
 * @property int $article_id
 * @property bool $helpful
 * @property string|null $comment
 * @property string $visitor
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Article $article
 */
class Feedback extends Model
{
    protected $fillable = ['helpful', 'comment', 'visitor'];

    protected $hidden = ['visitor'];

    protected function casts(): array
    {
        return ['helpful' => 'boolean', 'article_id' => 'integer'];
    }

    public function getTable(): string
    {
        return config('help-center.table_prefix', 'help_').'feedback';
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
