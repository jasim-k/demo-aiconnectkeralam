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
     * A machine-readable rendering of the cart, for JSON tool output.
     *
     * @return array{items: array<int, array<string, mixed>>, count: int, total: int, total_formatted: string, empty: bool}
     */
    protected function cartArray(Request $request, CartService $carts): array
    {
        $cart = $carts->present($this->resolveCart($request, $carts));

        $items = array_map(fn (array $item): array => [
            ...$item,
            'unit_price_formatted' => Money::inr($item['unit_price']),
            'subtotal_formatted' => Money::inr($item['subtotal']),
        ], $cart['items']);

        return [
            'items' => $items,
            'count' => $cart['count'],
            'total' => $cart['total'],
            'total_formatted' => Money::inr($cart['total']),
            'empty' => $items === [],
        ];
    }
}
