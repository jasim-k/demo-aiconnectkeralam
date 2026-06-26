<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(Request $request): Response
    {
        $cart = $this->cart->forSession($this->cart->sessionKeyFor($request->user(), $request->session()->getId()));

        return Inertia::render('store/cart', [
            'cart' => $this->cart->present($cart),
        ]);
    }

    public function add(AddToCartRequest $request): RedirectResponse
    {
        $cart = $this->cart->forSession($this->cart->sessionKeyFor($request->user(), $request->session()->getId()));
        $product = Product::findOrFail($request->integer('product_id'));

        $this->cart->add($cart, $product, $request->integer('quantity') ?: 1);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$product->name} added to cart."]);

        return back();
    }

    public function update(UpdateCartRequest $request): RedirectResponse
    {
        $cart = $this->cart->forSession($this->cart->sessionKeyFor($request->user(), $request->session()->getId()));
        $product = Product::findOrFail($request->integer('product_id'));

        $this->cart->updateQuantity($cart, $product, $request->integer('quantity'));

        return back();
    }

    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $cart = $this->cart->forSession($this->cart->sessionKeyFor($request->user(), $request->session()->getId()));
        $this->cart->remove($cart, Product::findOrFail((int) $validated['product_id']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item removed from cart.']);

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $cart = $this->cart->forSession($this->cart->sessionKeyFor($request->user(), $request->session()->getId()));
        $this->cart->clear($cart);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cart cleared.']);

        return back();
    }
}
