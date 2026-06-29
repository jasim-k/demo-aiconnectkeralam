<?php

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

beforeEach(fn () => pinGuestSession());

it('redirects guests to login when trying to checkout', function () {
    $this->get('/checkout')->assertRedirect(route('login'));
    $this->post('/checkout', [])->assertRedirect(route('login'));
});

it('redirects to the cart when checking out with an empty cart', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/checkout')->assertRedirect('/cart');
});

it('shows the checkout page with the cart and prefilled customer', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create(['stock' => 10]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->get('/checkout')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/checkout')
            ->has('cart.items', 1)
            ->where('customer.email', $user->email)
        );
});

it('prefills the saved profile phone and address at checkout', function () {
    $user = User::factory()->create([
        'phone' => '9999999999',
        'address' => 'Kochi, Kerala',
    ]);
    $this->actingAs($user);

    $product = Product::factory()->create(['stock' => 10]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->get('/checkout')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customer.phone', '9999999999')
            ->where('customer.address', 'Kochi, Kerala')
        );
});

it('validates the checkout form', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create(['stock' => 10]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->post('/checkout', [])
        ->assertSessionHasErrors(['customer_name', 'email', 'phone', 'address']);
});

it('places an order, deducts stock and clears the cart', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create(['stock' => 10, 'price' => 132900]);
    $accessory = Product::factory()->create(['stock' => 50, 'price' => 4900]);

    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
    $this->post('/cart/add', ['product_id' => $accessory->id, 'quantity' => 2]);

    $response = $this->post('/checkout', [
        'customer_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '9999999999',
        'address' => 'Kochi, Kerala',
    ]);

    $order = Order::first();
    expect($order)->not->toBeNull();
    $response->assertRedirect("/checkout/success/{$order->order_number}");

    expect($order->total)->toBe(132900 + 2 * 4900)
        ->and($order->items)->toHaveCount(2)
        ->and($order->status)->toBe('confirmed');

    expect(Product::find($product->id)->stock)->toBe(9)
        ->and(Product::find($accessory->id)->stock)->toBe(48);

    expect(CartItem::count())->toBe(0);
});

it('shows the order success page to the buyer', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create(['stock' => 10]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->post('/checkout', [
        'customer_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '9999999999',
        'address' => 'Kochi, Kerala',
    ]);

    $order = Order::first();

    $this->get("/checkout/success/{$order->order_number}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/order-success')
            ->where('order.order_number', $order->order_number)
        );
});

it('forbids viewing an order that does not belong to the session', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::create([
        'order_number' => 'APL-20260101-ABCDE',
        'customer_name' => 'Someone Else',
        'email' => 'other@example.com',
        'phone' => '1234567890',
        'address' => 'Elsewhere',
        'total' => 50000,
        'status' => 'confirmed',
    ]);

    $this->get("/checkout/success/{$order->order_number}")->assertForbidden();
});
