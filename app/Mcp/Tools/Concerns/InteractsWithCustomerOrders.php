<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Order;
use Laravel\Mcp\Request;

trait InteractsWithCustomerOrders
{
    /**
     * Find an order by its number, scoped to the authenticated caller.
     *
     * On the OAuth-protected web server the lookup is constrained to the
     * authenticated user's own orders, so one customer can never read another
     * customer's order by guessing its number. The trusted local stdio server
     * has no user and may look up any order.
     */
    protected function findOrderForRequest(Request $request, string $orderNumber): ?Order
    {
        $user = $request->user();

        return Order::with('items')
            ->when($user !== null, fn ($query) => $query->where('user_id', $user->getAuthIdentifier()))
            ->where('order_number', $orderNumber)
            ->first();
    }
}
