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

#[Name('compare_products')]
#[Description('Compare 2 to 4 products side by side by their id, showing series, price, storage, colour and stock to help a customer choose.')]
class CompareProductsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:2', 'max:4'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ], [
            'product_ids.min' => 'Provide at least two product ids to compare.',
        ]);

        $products = Product::findMany($validated['product_ids']);

        if ($products->count() < 2) {
            return Response::error('Could not find at least two of the given products to compare.');
        }

        $rows = [];
        foreach (['Name' => 'name', 'Series' => 'series', 'Storage' => 'storage', 'Colour' => 'color'] as $label => $attr) {
            $rows[] = $label.': '.$products->map(fn ($p) => $p->{$attr} ?? '—')->implode('  |  ');
        }
        $rows[] = 'Price: '.$products->map(fn ($p) => Money::inr($p->price))->implode('  |  ');
        $rows[] = 'Stock: '.$products->map(fn ($p) => $p->stock > 0 ? "{$p->stock}" : 'Sold out')->implode('  |  ');

        $header = 'Comparing: '.$products->map(fn ($p) => "#{$p->id}")->implode(', ');

        $cheapest = $products->sortBy('price')->first();

        return Response::text(
            $header."\n\n".implode("\n", $rows)
            ."\n\nMost affordable: {$cheapest->name} (#{$cheapest->id}) at ".Money::inr($cheapest->price)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product_ids' => $schema->array()
                ->description('A list of 2 to 4 product ids to compare.')
                ->items($schema->integer())
                ->min(2)
                ->max(4)
                ->required(),
        ];
    }
}
