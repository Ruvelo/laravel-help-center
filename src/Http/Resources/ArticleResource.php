<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Support\Outline;
use Ruvelo\HelpCenter\Support\Snippet;

/**
 * @mixin Article
 */
final class ArticleResource extends JsonResource
{
    /** Include body, rendered HTML and outline (single-article responses). */
    private bool $full = false;

    private ?string $query = null;

    public function full(): self
    {
        $this->full = true;

        return $this;
    }

    /**
     * Add a highlighted `snippet_html` for this search query.
     */
    public function matching(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $article = [
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => route('help.articles.show', $this->resource),
            'excerpt' => $this->summary(),
            'status' => $this->status,
            'category' => $this->category === null ? null : [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
            ],
            'position' => $this->position,
            'views' => $this->views,
            'helpful' => ['yes' => $this->helpful_count, 'no' => $this->not_helpful_count],
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];

        if ($this->query !== null) {
            $article['snippet_html'] = Snippet::for($this->resource, $this->query);
        }

        if (! $this->full) {
            return $article;
        }

        $html = $this->html();

        return $article + [
            'version' => $this->version,
            'body' => $this->body,
            'html' => $html,
            'outline' => Outline::of($html),
        ];
    }
}
