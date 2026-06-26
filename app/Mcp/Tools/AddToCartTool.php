<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\InteractsWithStoreCart;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('add_to_cart')]
#[Description('Add a product to the cart by its id. Use search_products first to find the exact variant id. Quantity defaults to 1 and is merged with any existing line for that product.')]
class AddToCartTool extends Tool
{
    use InteractsWithStoreCart;

    public function handle(Request $request, CartService $carts): Response
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);

        try {
            $carts->add($this->resolveCart($request, $carts), $product, $validated['quantity'] ?? 1);
        } catch (ValidationException $e) {
            return Response::error(implode(' ', $e->validator->errors()->all()));
        }

        return Response::text(
            "Added {$product->name} to the cart.\n\n".$this->cartSummary($request, $carts)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The id of the product variant to add (from search_products).')
                ->required(),
            'quantity' => $schema->integer()
                ->description('How many to add (default 1).'),
        ];
    }
}
