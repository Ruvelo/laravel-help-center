<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Mcp\Tools;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Ruvelo\HelpCenter\HelpCenter;
use Ruvelo\HelpCenter\Mcp\Presenter;

#[Name('list_categories')]
#[Title('List help categories')]
#[Description('List the help center\'s categories in menu order, with their slug, description, public URL and how many published articles each holds.')]
#[IsReadOnly]
#[IsIdempotent]
final class ListCategories extends Tool
{
    public function handle(): Response|ResponseFactory
    {
        $categories = HelpCenter::categories();

        if ($categories->isEmpty()) {
            return Response::text('The help center has no categories yet.');
        }

        return Response::structured([
            'categories' => $categories->map(Presenter::category(...))->values()->all(),
        ]);
    }
}
