<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Registrar;

// The MCP endpoint. Off by default: set HELP_CENTER_MCP=true (or help-center.mcp.enabled).
$server = config('help-center.mcp.server');
$path = config('help-center.mcp.path', 'mcp/help');

if (is_string($path) && $path !== '' && is_string($server) && is_subclass_of($server, Server::class)) {
    Route::middleware(config('help-center.mcp.middleware', ['api', 'auth:sanctum']))
        ->domain(config('help-center.domain'))
        ->group(fn () => app(Registrar::class)->web($path, $server)->name('help.mcp'));
}
