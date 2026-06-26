<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->value() ?: null,
            'series' => $request->string('series')->value() ?: null,
            'storage' => $request->string('storage')->value() ?: null,
            'color' => $request->string('color')->value() ?: null,
            'price_min' => $request->integer('price_min') ?: null,
            'price_max' => $request->integer('price_max') ?: null,
            'sort' => $request->string('sort')->value() ?: null,
        ];

        return Inertia::render('store/products', [
            'products' => $this->products->catalog($filters),
            'filterOptions' => $this->products->filterOptions(),
            'filters' => $filters,
        ]);
    }

    public function show(Product $product): Response
    {
        $variants = $product->variants();

        return Inertia::render('store/product', [
            'product' => $product,
            'storageOptions' => $variants->whereNotNull('storage')->pluck('storage')->unique()->values(),
            'colorOptions' => $variants->whereNotNull('color')->pluck('color')->unique()->values(),
            'variants' => $variants->map(fn (Product $variant): array => [
                'id' => $variant->id,
                'storage' => $variant->storage,
                'color' => $variant->color,
                'price' => $variant->price,
                'stock' => $variant->stock,
            ])->values(),
        ]);
    }
}
