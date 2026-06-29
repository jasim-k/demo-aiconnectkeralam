<?php

use App\Mcp\Servers\StoreServer;
use App\Mcp\Tools\AddToCartTool;
use App\Mcp\Tools\CheckoutTool;
use App\Mcp\Tools\ClearCartTool;
use App\Mcp\Tools\CompareProductsTool;
use App\Mcp\Tools\GetOrderDetailsTool;
use App\Mcp\Tools\GetProductDetailsTool;
use App\Mcp\Tools\SearchProductsTool;
use App\Mcp\Tools\SendTelegramConfirmationTool;
use App\Mcp\Tools\ViewCartTool;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

it('searches products', function () {
    Product::factory()->create(['name' => 'iPhone 17 Pro 256GB', 'model' => 'iPhone 17 Pro']);
    Product::factory()->create(['name' => 'AirPods Pro', 'model' => 'AirPods Pro']);

    StoreServer::tool(SearchProductsTool::class, ['query' => 'iPhone 17'])
        ->assertOk()
        ->assertSee('iPhone 17 Pro 256GB');
});

it('returns product details with variants', function () {
    $product = Product::factory()->create(['model' => 'iPhone 17 Pro', 'storage' => '256GB']);
    Product::factory()->create(['model' => 'iPhone 17 Pro', 'storage' => '512GB']);

    StoreServer::tool(GetProductDetailsTool::class, ['product_id' => $product->id])
        ->assertOk()
        ->assertSee('storage_options')
        ->assertSee('512GB');
});

it('compares products', function () {
    $a = Product::factory()->create(['price' => 90000]);
    $b = Product::factory()->create(['price' => 50000]);

    StoreServer::tool(CompareProductsTool::class, ['product_ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertSee('most_affordable');
});

it('adds to the cart and views it', function () {
    $product = Product::factory()->create(['stock' => 5, 'price' => 1000, 'name' => 'Test Phone']);

    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk()
        ->assertSee('Test Phone')
        ->assertSee('"added"');

    StoreServer::tool(ViewCartTool::class, [])
        ->assertOk()
        ->assertSee('Test Phone')
        ->assertSee('"quantity":2');
});

it('rejects adding more than available stock', function () {
    $product = Product::factory()->create(['stock' => 1]);

    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 5])
        ->assertHasErrors();
});

it('clears the cart', function () {
    $product = Product::factory()->create(['stock' => 5]);
    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id])->assertOk();

    StoreServer::tool(ClearCartTool::class, [])->assertOk();
    StoreServer::tool(ViewCartTool::class, [])->assertSee('"empty":true');
});

it('checks out and deducts stock', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 132900]);
    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    StoreServer::tool(CheckoutTool::class, [
        'customer_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '9999999999',
        'address' => 'Kochi, Kerala',
    ])->assertOk()->assertSee('"confirmed":true');

    $order = Order::first();
    expect($order)->not->toBeNull()
        ->and($order->total)->toBe(132900)
        ->and(Product::find($product->id)->stock)->toBe(9);
});

it('falls back to the saved profile when checkout details are omitted', function () {
    $user = User::factory()->create([
        'name' => 'Profile Name',
        'email' => 'profile@example.com',
        'phone' => '9999999999',
        'address' => 'Kochi, Kerala',
    ]);

    $product = Product::factory()->create(['stock' => 10, 'price' => 132900]);
    StoreServer::actingAs($user)->tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    StoreServer::actingAs($user)->tool(CheckoutTool::class, [])
        ->assertOk()
        ->assertSee('"confirmed":true');

    $order = Order::first();
    expect($order->customer_name)->toBe('Profile Name')
        ->and($order->email)->toBe('profile@example.com')
        ->and($order->phone)->toBe('9999999999')
        ->and($order->address)->toBe('Kochi, Kerala');
});

it('reports which checkout details are missing when there is no saved profile', function () {
    $product = Product::factory()->create(['stock' => 10]);
    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    StoreServer::tool(CheckoutTool::class, [])
        ->assertSee('Missing customer details');
});

it('gets order details for a placed order', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 4900, 'name' => 'MagSafe Charger']);
    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 2])->assertOk();
    StoreServer::tool(CheckoutTool::class, [
        'customer_name' => 'Jane',
        'email' => 'jane@example.com',
        'phone' => '99999',
        'address' => 'Kochi',
    ])->assertOk();

    $order = Order::first();

    StoreServer::tool(GetOrderDetailsTool::class, ['order_number' => $order->order_number])
        ->assertOk()
        ->assertSee('MagSafe Charger');
});

it('refuses to send a telegram confirmation when the customer has not connected telegram', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 4900]);
    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 1])->assertOk();
    StoreServer::tool(CheckoutTool::class, [
        'customer_name' => 'Jane',
        'email' => 'jane@example.com',
        'phone' => '99999',
        'address' => 'Kochi',
    ])->assertOk();

    $order = Order::first();

    StoreServer::tool(SendTelegramConfirmationTool::class, ['order_number' => $order->order_number])
        ->assertHasErrors();
});
