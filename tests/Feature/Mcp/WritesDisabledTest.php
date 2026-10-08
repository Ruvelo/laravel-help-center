<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

use Laravel\Mcp\Request;
use Ruvelo\HelpCenter\Mcp\HelpCenterServer;
use Ruvelo\HelpCenter\Mcp\Tools\WriteArticle;
use Ruvelo\HelpCenter\Models\Article;

class WritesDisabledTest extends McpTestCase
{
    protected bool $allowWrites = false;

    public function test_writes_are_off_by_default_even_for_editors(): void
    {
        $defaults = require __DIR__.'/../../../config/help-center.php';
        $this->assertFalse($defaults['mcp']['enabled']);
        $this->assertFalse($defaults['mcp']['allow_writes']);
        $this->assertSame(['api', 'auth:sanctum'], $defaults['mcp']['middleware']);

        $editor = $this->editor();

        HelpCenterServer::actingAs($editor)->tools()->assertNotRegistered(WriteArticle::class);
        HelpCenterServer::actingAs($editor)->tool(WriteArticle::class, ['title' => 'x', 'body' => 'y'])->assertNotRegistered();

        $this->actingAs($editor);
        $this->assertTrue((new WriteArticle)->handle(new Request(['title' => 'x', 'body' => 'y']))->isError());
        $this->assertSame(0, Article::query()->count());
    }
}
