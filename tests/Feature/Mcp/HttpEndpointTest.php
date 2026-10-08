<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature\Mcp;

use Illuminate\Testing\TestResponse;
use Ruvelo\HelpCenter\Models\Article;
use Symfony\Component\HttpFoundation\Response;

class HttpEndpointTest extends McpTestCase
{
    /**
     * @param  array<string, mixed>  $params
     * @return TestResponse<Response>
     */
    private function rpc(string $method, array $params = [], string $path = '/mcp/help'): TestResponse
    {
        return $this->postJson($path, ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params], [
            'Accept' => 'application/json, text/event-stream',
        ]);
    }

    /**
     * @return list<string>
     */
    private function toolNames(TestResponse $response): array
    {
        return array_column($response->json('result.tools'), 'name');
    }

    public function test_a_public_help_center_answers_guests_with_published_content(): void
    {
        $this->article('Request a refund', 'Five days.');
        $this->article('Refund draft', 'Secret.', status: Article::DRAFT);

        $tools = $this->rpc('tools/list')->assertOk();
        $this->assertSame(['search_articles', 'read_article', 'list_categories'], $this->toolNames($tools));

        $search = $this->rpc('tools/call', ['name' => 'search_articles', 'arguments' => ['query' => 'refund']])->assertOk();
        $this->assertSame(1, $search->json('result.structuredContent.count'));
        $this->assertStringNotContainsString('Refund draft', (string) $search->getContent());

        $this->rpc('resources/templates/list')->assertOk()
            ->assertJsonPath('result.resourceTemplates.0.uriTemplate', 'help-center://articles/{slug}');

        $this->rpc('resources/read', ['uri' => 'help-center://articles/request-a-refund'])->assertOk()
            ->assertJsonPath('result.contents.0.mimeType', 'text/markdown');
    }

    public function test_the_server_introduces_itself_by_the_help_center_name(): void
    {
        config(['help-center.name' => 'Halyard Help']);

        $this->rpc('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'test', 'version' => '1']])
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'Halyard Help')
            ->assertJsonPath('result.serverInfo.version', '1.1.0');
    }

    public function test_editors_with_writes_on_get_the_write_tool_over_http(): void
    {
        $this->actingAs($this->editor());

        $this->assertContains('write_article', $this->toolNames($this->rpc('tools/list')->assertOk()));

        $this->rpc('tools/call', ['name' => 'write_article', 'arguments' => ['title' => 'Refunds', 'body' => 'x']])
            ->assertOk()
            ->assertJsonPath('result.isError', false);
        $this->assertSame(Article::DRAFT, Article::query()->sole()->status);
    }

    public function test_guests_cannot_call_the_write_tool_over_http(): void
    {
        $this->rpc('tools/call', ['name' => 'write_article', 'arguments' => ['title' => 'Refunds', 'body' => 'x']])
            ->assertJsonPath('error.message', 'Tool [write_article] not found.');

        $this->assertSame(0, Article::query()->count());
    }

    public function test_get_is_refused_as_the_protocol_asks(): void
    {
        $this->get('/mcp/help')->assertStatus(405);
    }

    public function test_the_route_is_named(): void
    {
        $this->assertSame(url('mcp/help'), route('help.mcp'));
    }
}
