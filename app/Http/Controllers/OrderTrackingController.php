<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    /**
     * Show the order tracking page, including the most recently looked-up order.
     */
    public function index(Request $request): Response
    {
        $orderNumber = $request->session()->get('tracked_order');

        $order = $orderNumber
            ? Order::with('items')->where('order_number', $orderNumber)->first()
            : null;

        return Inertia::render('store/track', [
            'order' => $order,
        ]);
    }

    /**
     * Look up an order by its number and email, then redirect back to the tracker.
     *
     * @throws ValidationException
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where('email', $validated['email'])
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'order_number' => 'No order found with that order number and email.',
            ]);
        }

        $request->session()->put('tracked_order', $order->order_number);

        return to_route('orders.track');
    }
}
