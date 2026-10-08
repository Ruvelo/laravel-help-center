<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

use Laravel\Mcp\Server\Registrar;

class DisabledServerTest extends McpTestCase
{
    protected function mcpConfig(): array
    {
        return ['enabled' => false, 'local' => true];
    }

    public function test_nothing_is_registered_while_disabled(): void
    {
        $this->postJson('/mcp/help', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertNotFound();
        $this->assertNull($this->app->make(Registrar::class)->getLocalServer('help-center'));
    }
}
