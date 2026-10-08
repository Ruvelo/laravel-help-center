<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

use Laravel\Mcp\Server;
use Ruvelo\HelpCenter\Tests\TestCase;

abstract class McpTestCase extends TestCase
{
    /** Overridden by tests that need writes on. */
    protected bool $allowWrites = true;

    protected function setUp(): void
    {
        if (! class_exists(Server::class)) {
            $this->markTestSkipped('laravel/mcp is not installed.');
        }

        parent::setUp();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('help-center.mcp.enabled', true);
        $app['config']->set('help-center.mcp.allow_writes', $this->allowWrites);
        // A public help center: no token needed to read.
        $app['config']->set('help-center.mcp.middleware', ['api']);

        foreach ($this->mcpConfig() as $key => $value) {
            $app['config']->set("help-center.mcp.{$key}", $value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function mcpConfig(): array
    {
        return [];
    }
}
