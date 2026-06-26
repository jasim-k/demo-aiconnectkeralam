<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Order;
use App\Support\Money;

trait FormatsOrders
{
    /**
     * A machine-readable representation of an order for JSON tool output.
     *
     * @return array<string, mixed>
     */
    protected function orderArray(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'placed_at' => $order->created_at?->toIso8601String(),
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->email,
                'phone' => $order->phone,
            ],
            'address' => $order->address,
            'items' => $order->items
                ->map(fn ($item): array => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'unit_price_formatted' => Money::inr($item->price),
                    'subtotal' => $item->price * $item->quantity,
                    'subtotal_formatted' => Money::inr($item->price * $item->quantity),
                ])
                ->all(),
            'total' => $order->total,
            'total_formatted' => Money::inr($order->total),
        ];
    }
}
