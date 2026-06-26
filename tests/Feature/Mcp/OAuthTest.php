<?php

use App\Models\User;
use Laravel\Passport\Passport;

/** The Accept header a real MCP client sends. */
function mcpHeaders(): array
{
    return ['Accept' => 'application/json, text/event-stream'];
}

it('rejects unauthenticated requests to the web MCP server', function () {
    $this->withHeaders(mcpHeaders())
        ->postJson('/mcp/store', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertUnauthorized();
});

it('allows requests authenticated with a Passport token', function () {
    Passport::actingAs(User::factory()->create());

    $response = $this->withHeaders(mcpHeaders())
        ->postJson('/mcp/store', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);

    expect($response->getStatusCode())->not->toBe(401);
});

it('advertises the OAuth protected-resource metadata', function () {
    $this->getJson('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonStructure(['authorization_servers']);
});
