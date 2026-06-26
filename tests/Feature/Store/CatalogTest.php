<?php

use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the home page with featured and latest products', function () {
    Product::factory()->featured()->create();
    Product::factory()->count(3)->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/home')
            ->has('featured')
            ->has('latest')
        );
});

it('lists products in the catalog', function () {
    Product::factory()->count(5)->create();

    $this->get('/products')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/products')
            ->has('products.data', 5)
            ->has('filterOptions')
        );
});

it('filters products by series', function () {
    Product::factory()->create(['series' => 'iPhone 17']);
    Product::factory()->create(['series' => 'iPhone 15']);

    $this->get('/products?series=iPhone 17')
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.series', 'iPhone 17')
        );
});

it('searches products by name', function () {
    Product::factory()->create(['name' => 'iPhone 17 Pro 256GB', 'model' => 'iPhone 17 Pro']);
    Product::factory()->create(['name' => 'AirPods Pro', 'model' => 'AirPods Pro']);

    $this->get('/products?search=AirPods')
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'AirPods Pro')
        );
});

it('sorts products by price ascending', function () {
    Product::factory()->create(['price' => 90000]);
    Product::factory()->create(['price' => 50000]);

    $this->get('/products?sort=price_asc')
        ->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.price', 50000)
        );
});

it('shows a product detail page with variants', function () {
    $product = Product::factory()->create(['model' => 'iPhone 17 Pro', 'storage' => '256GB', 'color' => 'Silver']);
    Product::factory()->create(['model' => 'iPhone 17 Pro', 'storage' => '512GB', 'color' => 'Silver']);

    $this->get("/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/product')
            ->where('product.id', $product->id)
            ->has('storageOptions', 2)
            ->has('variants', 2)
        );
});
