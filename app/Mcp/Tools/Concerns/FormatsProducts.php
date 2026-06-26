<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Collection;

trait FormatsProducts
{
    /**
     * A one-line summary of a product for list output.
     */
    protected function productLine(Product $product): string
    {
        $attributes = Collection::make([$product->color, $product->storage])->filter()->implode(', ');
        $stock = $product->stock > 0 ? "{$product->stock} in stock" : 'Sold out';

        return "[#{$product->id}] {$product->name}"
            .($attributes !== '' ? " — {$attributes}" : '')
            .' · '.Money::inr($product->price)
            ." · {$stock}";
    }
}
