<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\InteractsWithStoreCart;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('remove_from_cart')]
#[Description('Remove a product entirely from the cart by its id.')]
class RemoveFromCartTool extends Tool
{
    use InteractsWithStoreCart;

    public function handle(Request $request, CartService $carts): Response
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::findOrFail((int) $validated['product_id']);

        $carts->remove($this->resolveCart($request, $carts), $product);

        return Response::text(
            "Removed {$product->name} from the cart.\n\n".$this->cartSummary($request, $carts)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The id of the product to remove from the cart.')
                ->required(),
        ];
    }
}
