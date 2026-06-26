<?php

use App\Mcp\Servers\StoreServer;
use Laravel\Mcp\Facades\Mcp;

// Local (stdio) server for Claude Desktop and the MCP Inspector. This runs as a
// trusted local subprocess, so it is not behind OAuth:
//   php artisan mcp:start store
Mcp::local('store', StoreServer::class);

// OAuth 2.1 (Passport) discovery + dynamic client registration routes.
Mcp::oauthRoutes();

// HTTP server protected by OAuth. Remote MCP clients must complete the OAuth
// flow and present a Bearer access token (scope: mcp:use):
//   php artisan mcp:inspector store-web
Mcp::web('/mcp/store', StoreServer::class)
    ->middleware('auth:api')
    ->name('store-web');
