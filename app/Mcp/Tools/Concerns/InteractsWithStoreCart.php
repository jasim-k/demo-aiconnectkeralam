<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Cart;
use App\Services\CartService;
use App\Support\Money;
use Laravel\Mcp\Request;

trait InteractsWithStoreCart
{
    /**
     * The session identifier for the current caller's cart.
     *
     * When the request is authenticated (the OAuth-protected web server), the
     * cart is namespaced to that user so carts never bleed between customers.
     * The trusted local stdio server has no user and falls back to a shared
     * session.
     */
    protected function cartSession(Request $request): string
    {
        $base = (string) config('store.mcp_cart_session', 'mcp-assistant');

        $user = $request->user();

        return $user !== null ? "{$base}-user-{$user->getAuthIdentifier()}" : $base;
    }

    /**
     * Resolve (or create) the cart for the current caller.
     */
    protected function resolveCart(Request $request, CartService $carts): Cart
    {
        return $carts->forSession($this->cartSession($request));
    }

    /**
     * A human-readable rendering of the cart, for tool output.
     */
    protected function cartSummary(Request $request, CartService $carts): string
    {
        $cart = $carts->present($this->resolveCart($request, $carts));

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
