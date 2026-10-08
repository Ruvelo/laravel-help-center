<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ruvelo\HelpCenter\Models\Category;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'description' => $this->description,
            'position' => $this->position,
            'url' => route('help.categories.show', $this->resource),
            'article_count' => $this->whenNotNull($this->published_articles_count),
            'articles' => ArticleResource::collection($this->whenLoaded('articles')),
        ];
    }
}
