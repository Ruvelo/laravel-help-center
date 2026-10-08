<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

use Illuminate\Contracts\Console\Kernel;
use Laravel\Mcp\Server\Registrar;

class LocalServerTest extends McpTestCase
{
    protected function mcpConfig(): array
    {
        return ['path' => null, 'local' => true];
    }

    public function test_the_server_can_run_over_stdio_only(): void
    {
        $this->assertNotNull($this->app->make(Registrar::class)->getLocalServer('help-center'));
        $this->postJson('/mcp/help', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertNotFound();
        $this->assertTrue(array_key_exists('mcp:start', $this->app->make(Kernel::class)->all()));
    }
}
