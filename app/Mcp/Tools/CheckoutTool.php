<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\FormatsOrders;
use App\Mcp\Tools\Concerns\InteractsWithStoreCart;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('checkout')]
#[Description('Place the order for the current cart. Validates the cart and stock, creates the order, deducts stock and clears the cart. The customer name, email, phone and shipping address are required; any omitted field falls back to the signed-in customer\'s saved profile.')]
class CheckoutTool extends Tool
{
    use FormatsOrders;
    use InteractsWithStoreCart;

    public function handle(Request $request, CheckoutService $checkout, CartService $carts): Response
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $customer = [
            'customer_name' => $validated['customer_name'] ?? $user?->name,
            'email' => $validated['email'] ?? $user?->email,
            'phone' => $validated['phone'] ?? $user?->phone,
            'address' => $validated['address'] ?? $user?->address,
        ];

        $missing = array_keys(array_filter($customer, fn ($value): bool => blank($value)));

        if ($missing !== []) {
            return Response::error('Missing customer details: '.implode(', ', $missing).'. Provide them or save them in profile settings.');
        }

        $cart = $this->resolveCart($request, $carts);

        try {
            $order = $checkout->place($cart, [
                'customer_name' => (string) $customer['customer_name'],
                'email' => (string) $customer['email'],
                'phone' => (string) $customer['phone'],
                'address' => (string) $customer['address'],
            ], $user?->getAuthIdentifier());
        } catch (ValidationException $e) {
            return Response::error(implode(' ', $e->validator->errors()->all()));
        }

        return Response::json([
            'confirmed' => true,
            'order' => $this->orderArray($order),
            'next_step' => "Call send_telegram_confirmation with order_number \"{$order->order_number}\" to notify the customer.",
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_name' => $schema->string()->description('Full name of the customer. Falls back to the signed-in customer\'s saved profile when omitted.'),
            'email' => $schema->string()->description('Customer email address. Falls back to the saved profile when omitted.'),
            'phone' => $schema->string()->description('Customer phone number. Falls back to the saved profile when omitted.'),
            'address' => $schema->string()->description('Full shipping address. Falls back to the saved profile when omitted.'),
        ];
    }
}
