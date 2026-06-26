<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CheckoutService $checkout,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $cart = $this->cart->forSession($request->session()->getId());

        if ($cart->load('items')->isEmpty()) {
            return to_route('cart.index');
        }

        return Inertia::render('store/checkout', [
            'cart' => $this->cart->present($cart),
            'customer' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $this->cart->forSession($request->session()->getId());

        $order = $this->checkout->place($cart, $request->customerDetails(), $request->user()->id);

        $request->session()->push('order_numbers', $order->order_number);

        return to_route('checkout.success', $order);
    }

    public function success(Request $request, Order $order): Response
    {
        abort_unless(
            in_array($order->order_number, $request->session()->get('order_numbers', []), true),
            HttpResponse::HTTP_FORBIDDEN,
        );

        $order->load('items');

        return Inertia::render('store/order-success', [
            'order' => $order,
        ]);
    }
}
