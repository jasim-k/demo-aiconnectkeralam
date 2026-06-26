<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_product_details')]
#[Description('Get full details for a single product by its id, including description, price, stock, and the other storage/colour variants available for the same model.')]
class GetProductDetailsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);
        $variants = $product->variants();

        $stock = $product->stock > 0 ? "{$product->stock} in stock" : 'Sold out';

        $lines = [
            "{$product->name} (#{$product->id})",
            "Series: {$product->series}",
            "Model: {$product->model}",
            'Colour: '.($product->color ?? '—'),
            'Storage: '.($product->storage ?? '—'),
            'Price: '.Money::inr($product->price),
            "SKU: {$product->sku}",
            "Availability: {$stock}",
            '',
            $product->description,
        ];

        $storageOptions = $variants->whereNotNull('storage')->pluck('storage')->unique()->values();
        $colorOptions = $variants->whereNotNull('color')->pluck('color')->unique()->values();

        if ($storageOptions->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Storage options: '.$storageOptions->implode(', ');
        }

        if ($colorOptions->isNotEmpty()) {
            $lines[] = 'Colour options: '.$colorOptions->implode(', ');
        }

        if ($variants->count() > 1) {
            $lines[] = '';
            $lines[] = 'Variants (id · storage · colour · price · stock):';
            foreach ($variants as $variant) {
                $lines[] = "  #{$variant->id} · ".($variant->storage ?? '—').' · '
                    .($variant->color ?? '—').' · '.Money::inr($variant->price)
                    ." · {$variant->stock} in stock";
            }
        }

        return Response::text(implode("\n", $lines));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The id of the product to look up (from search_products).')
                ->required(),
        ];
    }
}
