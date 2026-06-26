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

#[Name('get_order_details')]
#[Description('Look up a placed order by its order number and return the customer, items and total.')]
class GetOrderDetailsTool extends Tool
{
    use FormatsOrders;
    use InteractsWithCustomerOrders;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
        ]);

        $order = $this->findOrderForRequest($request, $validated['order_number']);

        if ($order === null) {
            return Response::error("No order found with number \"{$validated['order_number']}\".");
        }

        return Response::json([
            'order' => $this->orderArray($order),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->description('The order number returned by checkout, e.g. "APL-20260626-AB12C".')
                ->required(),
        ];
    }
}
