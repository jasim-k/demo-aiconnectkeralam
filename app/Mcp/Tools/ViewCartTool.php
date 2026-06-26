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

#[Name('view_cart')]
#[Description('View the current contents of the cart, including each line item, quantities and the grand total.')]
class ViewCartTool extends Tool
{
    use InteractsWithStoreCart;

    public function handle(Request $request, CartService $carts): Response
    {
        return Response::text($this->cartSummary($request, $carts));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
