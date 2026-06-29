<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Resolve the cart session key for a visitor.
     *
     * Authenticated customers share a single cart across every channel (web
     * storefront, MCP server and the Telegram assistant) via a user-namespaced
     * key, so items added in one place appear everywhere. Guests fall back to
     * their browser session id.
     */
    public function sessionKeyFor(?Authenticatable $user, string $guestSessionId): string
    {
        if ($user !== null) {
            $base = (string) config('store.mcp_cart_session', 'mcp-assistant');

            return "{$base}-user-{$user->getAuthIdentifier()}";
        }

        return $guestSessionId;
    }

    /**
     * Resolve (or create) the cart for the given session identifier.
     */
    public function forSession(string $sessionId): Cart
    {
        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    /**
     * Resolve the cart for a session without creating one if it does not exist.
     */
    public function existingForSession(string $sessionId): ?Cart
    {
        return Cart::where('session_id', $sessionId)->first();
    }

    /**
     * Lightweight summary used for the navbar badge (shared on every request).
     *
     * @return array{count: int, total: int}
     */
    public function summary(?Cart $cart): array
    {
        if ($cart === null) {
            return ['count' => 0, 'total' => 0];
        }

        $cart->loadMissing('items');

        return [
            'count' => $cart->itemCount(),
            'total' => $cart->total(),
        ];
    }

    /**
     * Full cart payload shaped for the storefront cart/checkout pages.
     *
     * @return array{items: array<int, array<string, mixed>>, count: int, total: int}
     */
    public function present(Cart $cart): array
    {
        $cart->load('items.product');

        $items = $cart->items
            ->sortBy('id')
            ->map(fn ($item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'model' => $item->product->model,
                'storage' => $item->product->storage,
                'color' => $item->product->color,
                'image' => $item->product->image,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'stock' => $item->product->stock,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'count' => $cart->itemCount(),
            'total' => $cart->total(),
        ];
    }

    /**
     * Add a product to the cart, merging quantity with any existing line item.
     *
     * @throws ValidationException when the product cannot satisfy the requested stock
     */
    public function add(Cart $cart, Product $product, int $quantity = 1): CartItem
    {
        $quantity = max(1, $quantity);

        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $requested = ($item->exists ? $item->quantity : 0) + $quantity;

        $this->guardStock($product, $requested);

        $item->quantity = $requested;
        $item->unit_price = $product->price;
        $item->subtotal = $product->price * $requested;
        $item->save();

        return $item;
    }

    /**
     * Set an explicit quantity for a product already in the cart.
     *
     * @throws ValidationException
     */
    public function updateQuantity(Cart $cart, Product $product, int $quantity): ?CartItem
    {
        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item === null) {
            throw ValidationException::withMessages([
                'product_id' => 'This product is not in your cart.',
            ]);
        }

        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $this->guardStock($product, $quantity);

        $item->quantity = $quantity;
        $item->unit_price = $product->price;
        $item->subtotal = $product->price * $quantity;
        $item->save();

        return $item;
    }

    public function remove(Cart $cart, Product $product): void
    {
        $cart->items()->where('product_id', $product->id)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * @throws ValidationException
     */
    private function guardStock(Product $product, int $quantity): void
    {
        if (! $product->hasStockFor($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock} unit(s) of {$product->name} are in stock.",
            ]);
        }
    }
}
