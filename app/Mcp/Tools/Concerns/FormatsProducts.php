<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Product;
use App\Support\Money;

trait FormatsProducts
{
    /**
     * A machine-readable representation of a product for JSON tool output.
     *
     * @return array<string, mixed>
     */
    protected function productArray(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'series' => $product->series,
            'model' => $product->model,
            'color' => $product->color,
            'storage' => $product->storage,
            'sku' => $product->sku,
            'price' => $product->price,
            'price_formatted' => Money::inr($product->price),
            'stock' => $product->stock,
            'in_stock' => $product->stock > 0,
        ];
    }
}
