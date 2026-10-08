<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Support\ArticleLinks;

/**
 * Markdown to HTML. Raw HTML in the source is escaped and javascript:-style
 * links are dropped, so any editor's text is safe to show the public.
 *
 * The CommonMark pipeline is built once and reused; each render runs at
 * most one query, to learn which linked articles the reader can see.
 */
class Renderer
{
    private ?MarkdownConverter $converter = null;

    /** @var array<string, string> link slug => the article's real slug */
    private array $visible = [];

    public function render(string $markdown): string
    {
        $this->visible = $this->resolve(ArticleLinks::targets($markdown));

        return (string) $this->converter()->convert($markdown);
    }

    /**
     * Rebuild the pipeline on next use, e.g. after adding an extension.
     */
    public function reset(): void
    {
        $this->converter = null;
    }

    /**
     * Link targets match an article's slug, or its current title (so
     * [[New title]] works after a rename, and [[Old title]] keeps working
     * because the slug never changes). Drafts only resolve for editors.
     *
     * @param  list<string>  $slugs
     * @return array<string, string>
     */
    private function resolve(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $map = [];
        foreach (Article::query()->visibleTo(auth()->user())->whereIn('slug', $slugs)->pluck('slug') as $slug) {
            $map[$slug] = $slug;
        }

        // A renamed article keeps its slug; find links to its new title.
        $missing = array_values(array_diff($slugs, array_keys($map)));
        if ($missing !== []) {
            Article::query()->visibleTo(auth()->user())->select(['slug', 'title'])->cursor()
                ->each(function (Article $article) use (&$map, $missing): void {
                    $titleSlug = Article::slugFor($article->title);
                    if (in_array($titleSlug, $missing, true)) {
                        $map[$titleSlug] ??= $article->slug;
                    }
                });
        }

        return $map;
    }

    private function converter(): MarkdownConverter
    {
        return $this->converter ??= $this->build();
    }

    private function build(): MarkdownConverter
    {
        $environment = new Environment(array_replace_recursive([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'symbol' => '#',
                'insert' => 'after',
                'title' => 'Link to this section',
            ],
        ], config('help-center.markdown.options', [])));

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);

        foreach (config('help-center.markdown.extensions', []) as $extension) {
            /** @var ExtensionInterface $extension */
            $extension = is_string($extension) ? app($extension) : $extension;
            $environment->addExtension($extension);
        }

        $environment->addInlineParser(new ArticleLinkParser(fn (string $slug): ?string => $this->visible[$slug] ?? null), 100);

        foreach (HelpCenter::markdownExtenders() as $extend) {
            $extend($environment);
        }

        return new MarkdownConverter($environment);
    }
}
