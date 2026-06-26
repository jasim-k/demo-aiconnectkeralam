<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\FormatsProducts;
use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_product_details')]
#[Description('Get full details for a single product by its id, including description, price, stock, and the other storage/colour variants available for the same model. When several variants are returned, present them to the customer as a single-choice selection so they pick exactly one.')]
class GetProductDetailsTool extends Tool
{
    use FormatsProducts;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);
        $variants = $product->variants();

        return Response::json([
            'product' => [
                ...$this->productArray($product),
                'description' => $product->description,
            ],
            'storage_options' => $variants->whereNotNull('storage')->pluck('storage')->unique()->values()->all(),
            'color_options' => $variants->whereNotNull('color')->pluck('color')->unique()->values()->all(),
            'variants' => $variants->map(fn ($variant) => $this->productArray($variant))->all(),
        ]);
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
