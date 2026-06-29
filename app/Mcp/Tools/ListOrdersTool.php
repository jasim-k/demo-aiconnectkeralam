<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\FormatsOrders;
use App\Mcp\Tools\Concerns\InteractsWithCustomerOrders;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list_orders')]
#[Description("List the signed-in customer's own placed orders, most recent first. Includes orders placed on the website. Use get_order_details for the full items of a specific order.")]
class ListOrdersTool extends Tool
{
    use FormatsOrders;
    use InteractsWithCustomerOrders;

    public function handle(Request $request): Response
    {
        if ($request->user() === null) {
            return Response::error('Listing orders requires a signed-in customer. Use get_order_details with a specific order number instead.');
        }

        $orders = $this->ordersForRequest($request);

        return Response::json([
            'count' => $orders->count(),
            'orders' => $orders->map(fn ($order): array => $this->orderSummaryArray($order))->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
