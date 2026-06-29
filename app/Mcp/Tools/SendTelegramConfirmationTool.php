<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\InteractsWithCustomerOrders;
use App\Services\Telegram\TelegramService;
use App\Support\TelegramOrderMessage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Name('send_telegram_confirmation')]
#[Description('Send an order confirmation message to the customer on Telegram for a given order number. The customer must have connected their Telegram account beforehand; the message is delivered to their linked chat.')]
class SendTelegramConfirmationTool extends Tool
{
    use InteractsWithCustomerOrders;

    public function handle(Request $request, TelegramService $telegram): Response
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
        ]);

        $order = $this->findOrderForRequest($request, $validated['order_number']);

        if ($order === null) {
            return Response::error("No order found with number \"{$validated['order_number']}\".");
        }

        $customer = $order->user;

        if ($customer === null || ! $customer->hasConnectedTelegram()) {
            return Response::error(
                'The customer has not connected their Telegram account, so no confirmation can be sent. '
                .'Ask them to connect Telegram from Settings → Telegram first.'
            );
        }

        try {
            $response = $telegram->sendMessage($customer->telegram_chat_id, TelegramOrderMessage::for($order));
        } catch (ConnectionException $e) {
            return Response::error('Could not reach Telegram: '.$e->getMessage());
        } catch (RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        if ($response->failed()) {
            return Response::error('Telegram API error: '.($response->json('description') ?? $response->status()));
        }

        return Response::json([
            'sent' => true,
            'order_number' => $order->order_number,
            'telegram_username' => $customer->telegram_username,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->description('The order number to send a confirmation for. The order\'s customer must have connected Telegram.')
                ->required(),
        ];
    }
}
