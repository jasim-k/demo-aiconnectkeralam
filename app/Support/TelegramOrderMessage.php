<?php

namespace App\Support;

use App\Models\Order;

class TelegramOrderMessage
{
    /**
     * Build the Telegram order confirmation message body for an order.
     */
    public static function for(Order $order): string
    {
        $order->loadMissing('items');

        $items = $order->items
            ->map(fn ($item): string => "• {$item->product_name} ×{$item->quantity}")
            ->implode("\n");

        return "🛒 Order Confirmed\n\n"
            ."Order {$order->order_number}\n\n"
            ."Items:\n{$items}\n\n"
            .'Total: '.Money::inr($order->total)."\n\n"
            .'Thank you for shopping with iPhone Store.';
    }
}
