<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Telegram\TelegramAssistantService;
use App\Services\Telegram\TelegramPairingService;
use App\Services\Telegram\TelegramService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramService $telegram,
        private TelegramPairingService $pairing,
        private TelegramAssistantService $assistant,
    ) {}

    /**
     * Handle an incoming Telegram bot update.
     *
     * Telegram only delivers `message` updates (see the allowed_updates set when
     * registering the webhook). The /start and /pair commands link the chat to an
     * application user; any other message from a paired customer is handed to the
     * AI shopping assistant.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->verifySecret($request);

        $chatId = $request->input('message.chat.id');
        $text = $request->input('message.text');

        if ($chatId === null || ! is_string($text)) {
            return response()->json(['ok' => true]);
        }

        $chatId = (string) $chatId;
        $username = $request->input('message.from.username');
        $username = is_string($username) ? $username : null;
        $text = trim($text);

        if (preg_match('/^\/(?:start|pair)(?:@\w+)?(?:\s+(?<code>\S+))?/i', $text, $matches)) {
            $this->reply($chatId, $this->handlePairing($matches['code'] ?? null, $chatId, $username));

            return response()->json(['ok' => true]);
        }

        $this->reply($chatId, $this->handleShopping($chatId, $text));

        return response()->json(['ok' => true]);
    }

    /**
     * Route a free-text message to the shopping assistant for the paired customer.
     */
    private function handleShopping(string $chatId, string $text): string
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if ($user === null) {
            return "👋 Welcome to the iPhone Store bot.\n\n"
                .'To start shopping, connect your account: generate a pairing code from '
                .'Settings → Telegram and send it here as: /pair YOURCODE';
        }

        if (! $this->assistant->isEnabled()) {
            return '🛠️ The shopping assistant is temporarily unavailable. Please try again shortly.';
        }

        try {
            return $this->assistant->reply($user, $text);
        } catch (Throwable $e) {
            report($e);

            return '⚠️ Sorry, something went wrong while handling that. Please try again.';
        }
    }

    /**
     * Attempt the pairing and return the message to send back to the chat.
     */
    private function handlePairing(?string $code, string $chatId, ?string $username): string
    {
        if ($code === null || $code === '') {
            return "👋 Welcome to the iPhone Store bot.\n\n"
                .'To connect your account, generate a pairing code from Settings → Telegram '
                .'and send it here as: /pair YOURCODE';
        }

        $user = $this->pairing->redeem($code, $chatId, $username);

        if ($user === null) {
            return "⚠️ That pairing code is invalid or has expired.\n\n"
                .'Generate a fresh code from Settings → Telegram and try again.';
        }

        return "✅ Connected! Your Telegram is now linked to {$user->name}'s iPhone Store account. "
            .'Order confirmations will arrive here.';
    }

    private function reply(string $chatId, string $message): void
    {
        try {
            $this->telegram->sendMessage($chatId, $message);
        } catch (ConnectionException) {
            // The webhook must always return 200 quickly; a failed reply is
            // non-fatal and Telegram will not retry a successful HTTP response.
        }
    }

    /**
     * Reject updates that do not carry the configured secret token.
     */
    private function verifySecret(Request $request): void
    {
        $secret = config('services.telegram.webhook_secret');

        if (blank($secret)) {
            return;
        }

        abort_unless(
            hash_equals((string) $secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')),
            403,
        );
    }
}
