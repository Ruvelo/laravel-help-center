<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Ruvelo\HelpCenter\Exceptions\ArticleAlreadyExists;
use Ruvelo\HelpCenter\Exceptions\EditConflict;
use Ruvelo\HelpCenter\Exceptions\InvalidTitle;
use Ruvelo\HelpCenter\Mcp\Presenter;
use Ruvelo\HelpCenter\Models\Article;

/**
 * Only listed for editors (the `help-center-edit` gate) when
 * `help-center.mcp.allow_writes` is on, and checked again on every call.
 */
#[Name('write_article')]
#[Title('Write a help article')]
#[Description('Create a help center article, or change one. To create, leave out slug and give a title and Markdown body; new articles are drafts unless status is "published". To change an article, first call read_article, then pass its slug and the version you read as base_version, plus only the fields to change. If someone saved in between, nothing is saved and you get the current version: read the article again and redo your change.')]
final class WriteArticle extends Tool
{
    public function shouldRegister(Request $request): bool
    {
        return Presenter::canWrite($request->user());
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        if (! Presenter::canWrite($request->user())) {
            return Response::error('You can\'t write help center articles here.');
        }

        $data = $request->validate([
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['required_without:slug', 'string', 'max:255'],
            'body' => ['required_without:slug', 'string', 'max:200000'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(Article::STATUSES)],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
            'base_version' => ['required_with:slug', 'integer', 'min:0'],
        ]);

        $changes = array_intersect_key($data, array_flip(['title', 'body', 'status', 'excerpt']));

        if (array_key_exists('category', $data)) {
            $category = is_string($data['category']) && trim($data['category']) !== '' ? Presenter::findCategory($data['category']) : null;

            if ($category === null && is_string($data['category']) && trim($data['category']) !== '') {
                return Response::error("There is no category “{$data['category']}”. Call list_categories to see them, or leave category out.");
            }
            $changes['category_id'] = $category?->id;
        }

        $updating = is_string($data['slug'] ?? null) && trim($data['slug']) !== '';
        $slug = $updating ? Article::slugFor((string) $data['slug']) : '';

        try {
            if (! $updating) {
                $article = (new Article)->commit($changes);
            } else {
                $article = $slug === '' ? null : Article::query()->where('slug', $slug)->first();

                if ($article === null) {
                    return Response::error("There is no article “{$data['slug']}”. Leave slug out to create a new one.");
                }

                $article->commit($changes, (int) $data['base_version']);
            }
        } catch (InvalidTitle $e) {
            return Response::error($e->getMessage());
        } catch (ArticleAlreadyExists $e) {
            return Response::error("An article called “{$e->article->title}” already exists (slug “{$e->article->slug}”, version {$e->article->version}). To change it, call read_article, then write_article with its slug and base_version.");
        } catch (EditConflict $e) {
            return Response::error("Not saved: “{$e->article->title}” changed since version {$e->expectedVersion} and is now at version {$e->currentVersion}. Call read_article again, redo your change on the new text, and pass base_version {$e->currentVersion}.");
        }

        return Response::structured([
            'saved' => true,
            'created' => ! $updating,
            ...Presenter::full($article->load('category')),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The article to change. Leave it out to create a new article.'),
            'base_version' => $schema->integer()->min(0)->description('Required with slug: the version read_article returned. Protects other people\'s edits.'),
            'title' => $schema->string()->description('The title, in sentence case. Required when creating. Renaming keeps the slug and URL.'),
            'body' => $schema->string()->description('The full article in Markdown, replacing the old text. Required when creating. Link to other articles with [[Article title]].'),
            'category' => $schema->string()->description('The category\'s slug or name (see list_categories). Give an empty string to leave the article uncategorised.'),
            'status' => $schema->string()->enum(Article::STATUSES)->description('"draft" (only editors see it) or "published" (public). New articles default to draft.'),
            'excerpt' => $schema->string()->description('A one-line summary shown in lists and search results.'),
        ];
    }
}
