<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\View\Components;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

/**
 * Contextual help inside your app: a compact list of published articles
 * linking into the help center.
 *
 *     <x-help::articles category="billing-invoices" :limit="5" />
 *     <x-help::articles search="refund" title="Refunds, explained" />
 */
class Articles extends Component
{
    /** @var Collection<int, Article> */
    public Collection $articles;

    public ?Category $categoryModel;

    public string $heading;

    public function __construct(
        public ?string $category = null,
        public ?string $search = null,
        public int $limit = 5,
        ?string $title = null,
    ) {
        $this->categoryModel = $category === null ? null : Category::query()
            ->where('slug', Article::slugFor($category))
            ->orWhere('name', $category)
            ->first();

        $search = trim((string) $search);

        $query = Article::query()->published()->with('category:id,name,slug,icon');

        if ($category !== null) {
            $query->where('category_id', $this->categoryModel->id ?? 0);
        }

        $this->articles = ($search !== '' ? $query->matching($search) : $query->ordered())
            ->limit(max(1, min($limit, 50)))
            ->get(['id', 'category_id', 'title', 'slug', 'excerpt', 'body']);

        $this->heading = $title ?? ($this->categoryModel !== null ? $this->categoryModel->name : 'Help articles');
    }

    public function render(): string
    {
        return 'help::components.articles';
    }
}
