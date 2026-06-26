<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\FormatsProducts;
use App\Services\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('search_products')]
#[Description('Search the Apple store catalog by name, model, series, colour or SKU. Returns matching products with their id, price and stock so you can fetch details or add them to the cart.')]
class SearchProductsTool extends Tool
{
    use FormatsProducts;

    public function handle(Request $request, ProductService $products): Response
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $results = $products->search($validated['query'], $validated['limit'] ?? 20);

        return Response::json([
            'query' => $validated['query'],
            'count' => $results->count(),
            'products' => $results->map(fn ($product) => $this->productArray($product))->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Search term, e.g. "iPhone 17 Pro", "AirPods", "Teal".')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of results to return (default 20).'),
        ];
    }
}
