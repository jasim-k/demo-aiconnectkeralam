<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Mcp\Request;

trait InteractsWithCustomerOrders
{
    /**
     * Get the authenticated caller's own orders, most recent first.
     *
     * Unlike findOrderForRequest, this never falls back to every order: a
     * caller with no authenticated user (the trusted local stdio server) gets
     * an empty collection, because "list my orders" is meaningless without a
     * customer to scope to.
     *
     * @return Collection<int, Order>
     */
    protected function ordersForRequest(Request $request, int $limit = 20): Collection
    {
        $user = $request->user();

        if ($user === null) {
            return new Collection;
        }

        return Order::with('items')
            ->where('user_id', $user->getAuthIdentifier())
            ->latest()
            ->limit($limit)
            ->get();
    }

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
