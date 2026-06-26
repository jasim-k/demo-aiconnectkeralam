<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\InteractsWithStoreCart;
use App\Services\CartService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('clear_cart')]
#[Description('Remove all items from the cart.')]
class ClearCartTool extends Tool
{
    use InteractsWithStoreCart;

    public function handle(Request $request, CartService $carts): Response
    {
        $carts->clear($this->resolveCart($request, $carts));

        return Response::text('The cart has been cleared. It is now empty.');
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
