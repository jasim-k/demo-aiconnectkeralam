<?php

use App\Models\CartItem;
use App\Models\Product;

beforeEach(fn () => pinGuestSession());

it('adds a product to the cart', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2])
        ->assertRedirect();

    $this->assertDatabaseHas('cart_items', [
        'product_id' => $product->id,
        'quantity' => 2,
        'subtotal' => $product->price * 2,
    ]);
});

it('merges quantity when adding the same product twice', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2]);

    expect(CartItem::where('product_id', $product->id)->sum('quantity'))->toBe(3);
});

it('rejects adding more than the available stock', function () {
    $product = Product::factory()->create(['stock' => 3]);

    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 5])
        ->assertSessionHasErrors('quantity');

    $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
});

it('updates the quantity of a cart item', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->patch('/cart/update', ['product_id' => $product->id, 'quantity' => 4])
        ->assertRedirect();

    $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 4]);
});

it('removes a cart item when its quantity is set to zero', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->patch('/cart/update', ['product_id' => $product->id, 'quantity' => 0]);

    $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
});

it('removes a product from the cart', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);

    $this->delete('/cart/remove', ['product_id' => $product->id])
        ->assertRedirect();

    $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
});

it('clears the cart', function () {
    $first = Product::factory()->create(['stock' => 10]);
    $second = Product::factory()->create(['stock' => 10]);

    $this->post('/cart/add', ['product_id' => $first->id, 'quantity' => 1]);
    $this->post('/cart/add', ['product_id' => $second->id, 'quantity' => 1]);

    $this->delete('/cart/clear')->assertRedirect();

    expect(CartItem::count())->toBe(0);
});

it('shares the cart summary on every page', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);
    $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2]);

    $this->get('/products')
        ->assertInertia(fn ($page) => $page
            ->where('cart.count', 2)
            ->where('cart.total', 2000)
        );
});
