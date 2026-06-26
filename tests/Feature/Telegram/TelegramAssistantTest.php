<?php

use App\Ai\Agents\TelegramShopAssistant;
use App\Mcp\Tools\AddToCartTool;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\McpServerTool;
use Laravel\Ai\Tools\Request as ToolRequest;

function shopUpdate(string $text, int|string $chatId = 4242): array
{
    return [
        'update_id' => 1,
        'message' => [
            'message_id' => 1,
            'from' => ['id' => $chatId, 'username' => 'janedoe'],
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'text' => $text,
        ],
    ];
}

function pairedUser(string $chatId = '4242'): User
{
    $user = User::factory()->create();

    $user->forceFill([
        'telegram_chat_id' => $chatId,
        'telegram_username' => 'janedoe',
        'telegram_connected_at' => now(),
    ])->save();

    return $user;
}

beforeEach(function () {
    config()->set('services.telegram.bot_token', 'test-token');
    config()->set('services.telegram.webhook_secret', null);
    config()->set('ai.providers.openai.key', 'test-key');
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
});

it('hands a paired customer message to the shopping assistant and replies on Telegram', function () {
    TelegramShopAssistant::fake(['Added the iPhone 17 Pro Max to your cart. Anything else?']);
    pairedUser('4242');

    $this->postJson(route('telegram.webhook'), shopUpdate('Add an iPhone 17 Pro Max', 4242))
        ->assertOk()
        ->assertJson(['ok' => true]);

    TelegramShopAssistant::assertPrompted('Add an iPhone 17 Pro Max');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage')
        && $request['chat_id'] === '4242'
        && str_contains($request['text'], 'Added the iPhone 17 Pro Max'));
});

it('runs the reused MCP tools scoped to the authenticated paired customer', function () {
    $user = pairedUser();
    $product = Product::factory()->create(['stock' => 5, 'price' => 1000, 'name' => 'Test Phone']);

    // The assistant authenticates the paired customer before prompting; the AI
    // SDK then runs the very same MCP tool in-process. This proves the tool is
    // scoped to that customer's cart.
    Auth::setUser($user);

    $result = (new McpServerTool(new AddToCartTool))
        ->handle(new ToolRequest(['product_id' => $product->id, 'quantity' => 2]));

    expect($result)->toContain('Test Phone');

    $session = config('store.mcp_cart_session').'-user-'.$user->id;
    $cart = app(CartService::class)->existingForSession($session);

    expect($cart)->not->toBeNull();
    expect((int) $cart->items()->where('product_id', $product->id)->value('quantity'))->toBe(2);
});

it('tells paired customers when the assistant is not configured', function () {
    config()->set('ai.providers.openai.key', null);
    TelegramShopAssistant::fake();
    pairedUser('4242');

    $this->postJson(route('telegram.webhook'), shopUpdate('hello', 4242))->assertOk();

    TelegramShopAssistant::assertNeverPrompted();
    Http::assertSent(fn ($request): bool => str_contains($request['text'], 'temporarily unavailable'));
});

it('prompts unpaired chats to connect before shopping', function () {
    TelegramShopAssistant::fake();

    $this->postJson(route('telegram.webhook'), shopUpdate('Add an iPhone', 9999))->assertOk();

    TelegramShopAssistant::assertNeverPrompted();
    Http::assertSent(fn ($request): bool => str_contains($request['text'], '/pair YOURCODE'));
});
