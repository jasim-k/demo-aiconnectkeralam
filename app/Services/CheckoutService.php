<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private OrderService $orderService,
        private CartService $cartService,
    ) {}

    /**
     * Validate the cart, create the order, deduct stock and clear the cart atomically.
     *
     * @param  array{customer_name: string, email: string, phone: string, address: string}  $customer
     *
     * @throws ValidationException
     */
    public function place(Cart $cart, array $customer, ?int $userId = null): Order
    {
        $cart->load('items.product');

        $this->validateCart($cart);

        return DB::transaction(function () use ($cart, $customer, $userId): Order {
            $this->validateAndDeductStock($cart);

            $order = $this->orderService->createFromCart($cart, $customer, $userId);

            $this->cartService->clear($cart);

            return $order;
        });
    }

    /**
     * @throws ValidationException
     */
    private function validateCart(Cart $cart): void
    {
        if ($cart->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }
    }

    /**
     * Lock each product row, verify stock is still available, then deduct it.
     *
     * @throws ValidationException
     */
    private function validateAndDeductStock(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $product = $item->product()->lockForUpdate()->first();

            if ($product === null || ! $product->hasStockFor($item->quantity)) {
                throw ValidationException::withMessages([
                    'cart' => "{$item->product->name} is no longer available in the requested quantity.",
                ]);
            }

            $product->decrement('stock', $item->quantity);
        }
    }
}
