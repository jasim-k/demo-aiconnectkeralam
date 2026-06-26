<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\InteractsWithCustomerOrders;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('send_telegram_confirmation')]
#[Description('Send an order confirmation message to Telegram for a given order number. Requires a configured Telegram bot token and chat id.')]
class SendTelegramConfirmationTool extends Tool
{
    use InteractsWithCustomerOrders;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'chat_id' => ['nullable', 'string'],
        ]);

        $order = $this->findOrderForRequest($request, $validated['order_number']);

        if ($order === null) {
            return Response::error("No order found with number \"{$validated['order_number']}\".");
        }

        $message = $this->buildMessage($order);

        $token = config('services.telegram.bot_token');
        $chatId = $validated['chat_id'] ?? config('services.telegram.chat_id');

        if (blank($token) || blank($chatId)) {
            return Response::json([
                'sent' => false,
                'configured' => false,
                'order_number' => $order->order_number,
                'message' => 'Telegram is not configured (set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID, or pass chat_id).',
                'preview' => $message,
            ]);
        }

        try {
            $response = Http::asJson()->timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
            ]);
        } catch (ConnectionException $e) {
            return Response::error('Could not reach Telegram: '.$e->getMessage());
        }

        if ($response->failed()) {
            return Response::error('Telegram API error: '.($response->json('description') ?? $response->status()));
        }

        return Response::json([
            'sent' => true,
            'configured' => true,
            'order_number' => $order->order_number,
        ]);
    }

    private function buildMessage(Order $order): string
    {
        $items = $order->items
            ->map(fn ($item) => "• {$item->product_name} ×{$item->quantity}")
            ->implode("\n");

        return "🛒 Order Confirmed\n\n"
            ."Order {$order->order_number}\n\n"
            ."Items\n{$items}\n\n"
            .'Total: '.Money::inr($order->total)."\n\n"
            .'Thank you for shopping!';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->description('The order number to send a confirmation for.')
                ->required(),
            'chat_id' => $schema->string()
                ->description('Optional Telegram chat id to send to (defaults to TELEGRAM_CHAT_ID).'),
        ];
    }
}
