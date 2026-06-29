<?php

use App\Mcp\Servers\StoreServer;
use App\Mcp\Tools\AddToCartTool;
use App\Mcp\Tools\CheckoutTool;
use App\Mcp\Tools\GetOrderDetailsTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\SendTelegramConfirmationTool;
use App\Mcp\Tools\ViewCartTool;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('records the authenticated user on orders placed through checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10, 'price' => 4900]);

    StoreServer::actingAs($user)->tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    StoreServer::actingAs($user)->tool(CheckoutTool::class, [
        'customer_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '99999',
        'address' => 'Kochi',
    ])->assertOk();

    expect(Order::sole()->user_id)->toBe($user->id);
});

it('lets a customer read only their own order', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->for($owner)->create(['order_number' => 'APL-OWNED-0001']);

    StoreServer::actingAs($owner)->tool(GetOrderDetailsTool::class, ['order_number' => $order->order_number])
        ->assertOk()
        ->assertSee('APL-OWNED-0001');
});

it('lists the customer their own orders via list_orders', function () {
    $owner = User::factory()->create();
    Order::factory()->for($owner)->create(['order_number' => 'APL-OLD-0001', 'created_at' => now()->subDay()]);
    Order::factory()->for($owner)->create(['order_number' => 'APL-NEW-0001', 'created_at' => now()]);

    StoreServer::actingAs($owner)->tool(ListOrdersTool::class, [])
        ->assertOk()
        ->assertSee('"count":2')
        ->assertSee('APL-NEW-0001')
        ->assertSee('APL-OLD-0001');
});

it('does not list another customer orders via list_orders', function () {
    $owner = User::factory()->create();
    Order::factory()->for($owner)->create(['order_number' => 'APL-MINE-0001']);

    $stranger = User::factory()->create();
    Order::factory()->for($stranger)->create(['order_number' => 'APL-THEIRS-0001']);

    StoreServer::actingAs($owner)->tool(ListOrdersTool::class, [])
        ->assertOk()
        ->assertSee('"count":1')
        ->assertSee('APL-MINE-0001');
});

it('hides another customer order via get_order_details', function () {
    $stranger = User::factory()->create();
    $order = Order::factory()->for(User::factory())->create(['order_number' => 'APL-SECRET-0001']);

    StoreServer::actingAs($stranger)->tool(GetOrderDetailsTool::class, ['order_number' => $order->order_number])
        ->assertHasErrors();
});

it('hides another customer order via send_telegram_confirmation', function () {
    $stranger = User::factory()->create();
    $order = Order::factory()->for(User::factory())->create(['order_number' => 'APL-SECRET-0002']);

    StoreServer::actingAs($stranger)->tool(SendTelegramConfirmationTool::class, ['order_number' => $order->order_number])
        ->assertHasErrors();
});

it('sends a telegram confirmation to the customer who connected telegram', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    config()->set('services.telegram.bot_token', 'test-token');

    $user = User::factory()->create();
    $user->forceFill([
        'telegram_chat_id' => '5551234',
        'telegram_username' => 'jane',
        'telegram_connected_at' => now(),
    ])->save();

    $product = Product::factory()->create(['stock' => 10, 'price' => 4900, 'name' => 'MagSafe Charger']);

    StoreServer::actingAs($user)->tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 2])->assertOk();
    StoreServer::actingAs($user)->tool(CheckoutTool::class, [
        'customer_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '99999',
        'address' => 'Kochi',
    ])->assertOk();

    $order = Order::sole();

    StoreServer::actingAs($user)->tool(SendTelegramConfirmationTool::class, ['order_number' => $order->order_number])
        ->assertOk()
        ->assertSee('"sent":true');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage')
        && $request['chat_id'] === '5551234'
        && str_contains($request['text'], 'Order Confirmed'));
});

it('keeps each customer cart isolated', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10, 'name' => 'Alice Phone']);

    StoreServer::actingAs($alice)->tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    StoreServer::actingAs($bob)->tool(ViewCartTool::class, [])
        ->assertOk()
        ->assertSee('"empty":true');
});
