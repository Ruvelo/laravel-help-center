<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

class GuardedEndpointTest extends McpTestCase
{
    protected function mcpConfig(): array
    {
        // Like the default ['api', 'auth:sanctum'], with the session guard
        // standing in for Sanctum, which isn't installed here.
        return ['middleware' => ['api', 'auth']];
    }

    public function test_guests_are_turned_away_and_signed_in_users_read(): void
    {
        $this->postJson('/mcp/help', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();

        $this->actingAs($this->user('Reader'));
        $this->postJson('/mcp/help', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertOk()
            ->assertJsonCount(3, 'result.tools');
    }
}
