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
#[Description('Place the order for the current cart. Validates the cart and stock, creates the order, deducts stock and clears the cart. Requires the customer name, email, phone and shipping address.')]
class CheckoutTool extends Tool
{
    use FormatsOrders;
    use InteractsWithStoreCart;

    public function handle(Request $request, CheckoutService $checkout, CartService $carts): Response
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
        ]);

        $cart = $this->resolveCart($request, $carts);

        try {
            $order = $checkout->place($cart, [
                'customer_name' => (string) $validated['customer_name'],
                'email' => (string) $validated['email'],
                'phone' => (string) $validated['phone'],
                'address' => (string) $validated['address'],
            ], $request->user()?->getAuthIdentifier());
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
            'customer_name' => $schema->string()->description('Full name of the customer.')->required(),
            'email' => $schema->string()->description('Customer email address.')->required(),
            'phone' => $schema->string()->description('Customer phone number.')->required(),
            'address' => $schema->string()->description('Full shipping address.')->required(),
        ];
    }
}
