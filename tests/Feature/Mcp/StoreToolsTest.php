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
        ->assertSee('Storage options:')
        ->assertSee('512GB');
});

it('compares products', function () {
    $a = Product::factory()->create(['price' => 90000]);
    $b = Product::factory()->create(['price' => 50000]);

    StoreServer::tool(CompareProductsTool::class, ['product_ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertSee('Most affordable');
});

it('adds to the cart and views it', function () {
    $product = Product::factory()->create(['stock' => 5, 'price' => 1000, 'name' => 'Test Phone']);

    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk()
        ->assertSee('Added Test Phone');

    StoreServer::tool(ViewCartTool::class, [])
        ->assertOk()
        ->assertSee('Test Phone')
        ->assertSee('×2');
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
    StoreServer::tool(ViewCartTool::class, [])->assertSee('cart is empty');
});

it('checks out and deducts stock', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 132900]);
    StoreServer::tool(AddToCartTool::class, ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    StoreServer::tool(CheckoutTool::class, [
        'customer_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '9999999999',
        'address' => 'Kochi, Kerala',
    ])->assertOk()->assertSee('Order confirmed');

    $order = Order::first();
    expect($order)->not->toBeNull()
        ->and($order->total)->toBe(132900)
        ->and(Product::find($product->id)->stock)->toBe(9);
});

it('gets order details and sends a telegram preview when unconfigured', function () {
    config()->set('services.telegram.bot_token', null);

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

    StoreServer::tool(SendTelegramConfirmationTool::class, ['order_number' => $order->order_number])
        ->assertOk()
        ->assertSee('Order Confirmed');
});
