<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Persist an order and its line items from a cart's contents.
     *
     * @param  array{customer_name: string, email: string, phone: string, address: string}  $customer
     */
    public function createFromCart(Cart $cart, array $customer): Order
    {
        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => $customer['customer_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'total' => $cart->total(),
            'status' => 'confirmed',
        ]);

        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
            ]);
        }

        return $order;
    }

    public function generateOrderNumber(): string
    {
        do {
            $number = 'APL-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
