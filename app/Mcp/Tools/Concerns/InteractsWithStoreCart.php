<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Cart;
use App\Services\CartService;
use App\Support\Money;

trait InteractsWithStoreCart
{
    /**
     * The session identifier shared by all MCP cart/checkout tools.
     */
    protected function cartSession(): string
    {
        return (string) config('store.mcp_cart_session', 'mcp-assistant');
    }

    /**
     * Resolve (or create) the shared MCP cart.
     */
    protected function resolveCart(CartService $carts): Cart
    {
        return $carts->forSession($this->cartSession());
    }

    /**
     * A human-readable rendering of the cart, for tool output.
     */
    protected function cartSummary(CartService $carts): string
    {
        $cart = $carts->present($this->resolveCart($carts));

        if ($cart['items'] === []) {
            return 'The cart is empty.';
        }

        $lines = ['Cart contents:'];

        foreach ($cart['items'] as $item) {
            $variant = trim(implode(' ', array_filter([$item['color'], $item['storage']])));
            $lines[] = "  • {$item['name']}".($variant !== '' ? " ({$variant})" : '')
                ." ×{$item['quantity']} — ".Money::inr($item['subtotal']);
        }

        $lines[] = '';
        $lines[] = "Items: {$cart['count']}  ·  Grand total: ".Money::inr($cart['total']);

        return implode("\n", $lines);
    }
}
