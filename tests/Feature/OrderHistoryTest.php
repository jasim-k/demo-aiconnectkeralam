<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

beforeEach(fn () => pinGuestSession());

it('redirects guests to login', function () {
    $this->get('/orders')->assertRedirect(route('login'));
});

it('shows the authenticated user their own orders, most recent first', function () {
    $user = User::factory()->create();

    $older = Order::factory()->for($user)->create(['created_at' => now()->subDay()]);
    $newer = Order::factory()->for($user)->create(['created_at' => now()]);
    OrderItem::factory()->for($newer)->create();

    $this->actingAs($user)
        ->get('/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/orders')
            ->has('orders', 2)
            ->where('orders.0.id', $newer->id)
            ->where('orders.1.id', $older->id)
            ->has('orders.0.items', 1)
        );
});

it('does not show orders belonging to other users', function () {
    $user = User::factory()->create();
    Order::factory()->for($user)->create();

    $other = User::factory()->create();
    Order::factory()->for($other)->create();

    $this->actingAs($user)
        ->get('/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/orders')
            ->has('orders', 1)
        );
});

it('links the placed order to the authenticated customer', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs($user);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->post('/checkout', [
        'customer_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '0500000000',
        'address' => '1 Demo Street',
    ])->assertRedirect();

    expect(Order::sole()->user_id)->toBe($user->id);
});
