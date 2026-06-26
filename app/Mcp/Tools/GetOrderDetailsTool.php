<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use App\Support\Money;
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
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
        ]);

        $order = Order::with('items')->where('order_number', $validated['order_number'])->first();

        if ($order === null) {
            return Response::error("No order found with number \"{$validated['order_number']}\".");
        }

        $items = $order->items
            ->map(fn ($item) => "  • {$item->product_name} ×{$item->quantity} — ".Money::inr($item->price * $item->quantity))
            ->implode("\n");

        return Response::text(
            "Order {$order->order_number} — ".ucfirst($order->status)."\n"
            ."Placed: {$order->created_at->toDayDateTimeString()}\n"
            ."Customer: {$order->customer_name} <{$order->email}>, {$order->phone}\n"
            ."Ship to: {$order->address}\n\n"
            ."{$items}\n\n"
            .'Total: '.Money::inr($order->total)
        );
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
