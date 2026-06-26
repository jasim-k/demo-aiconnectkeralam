<?php

use App\Models\Order;
use App\Models\Product;

beforeEach(fn () => pinGuestSession());

function makeTrackableOrder(string $email = 'jane@example.com'): Order
{
    $order = Order::create([
        'order_number' => 'APL-20260101-TRACK',
        'customer_name' => 'Jane Doe',
        'email' => $email,
        'phone' => '9999999999',
        'address' => 'Kochi, Kerala',
        'total' => 1000,
        'status' => 'confirmed',
    ]);

    $order->items()->create([
        'product_id' => Product::factory()->create()->id,
        'product_name' => 'iPhone 17',
        'quantity' => 1,
        'price' => 1000,
    ]);

    return $order;
}

it('renders the tracking page', function () {
    $this->get('/track')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('store/track')->where('order', null));
});

it('tracks an order with a matching number and email', function () {
    $order = makeTrackableOrder();

    $this->post('/track', ['order_number' => $order->order_number, 'email' => 'jane@example.com'])
        ->assertRedirect(route('orders.track'));

    $this->get('/track')
        ->assertInertia(fn ($page) => $page
            ->component('store/track')
            ->where('order.order_number', $order->order_number)
            ->has('order.items', 1)
        );
});

it('rejects an incorrect email', function () {
    $order = makeTrackableOrder();

    $this->post('/track', ['order_number' => $order->order_number, 'email' => 'wrong@example.com'])
        ->assertSessionHasErrors('order_number');
});

it('rejects an unknown order number', function () {
    $this->post('/track', ['order_number' => 'NOPE-123', 'email' => 'jane@example.com'])
        ->assertSessionHasErrors('order_number');
});
