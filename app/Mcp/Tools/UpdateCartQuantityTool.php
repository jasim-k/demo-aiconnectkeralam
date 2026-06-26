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

#[Name('update_cart_quantity')]
#[Description('Set the exact quantity for a product already in the cart. A quantity of 0 removes the line item.')]
class UpdateCartQuantityTool extends Tool
{
    use InteractsWithStoreCart;

    public function handle(Request $request, CartService $carts): Response
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);

        try {
            $carts->updateQuantity($this->resolveCart($carts), $product, $validated['quantity']);
        } catch (ValidationException $e) {
            return Response::error(implode(' ', $e->validator->errors()->all()));
        }

        return Response::text(
            "Updated {$product->name} to quantity {$validated['quantity']}.\n\n".$this->cartSummary($carts)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The id of the product in the cart.')
                ->required(),
            'quantity' => $schema->integer()
                ->description('The new quantity (0 removes the item).')
                ->required(),
        ];
    }
}
