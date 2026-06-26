<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramPairingService;
use App\Services\Telegram\TelegramService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramService $telegram,
        private TelegramPairingService $pairing,
    ) {}

    /**
     * Handle an incoming Telegram bot update.
     *
     * Telegram only delivers `message` updates (see the allowed_updates set when
     * registering the webhook). We look for the /start and /pair commands and use
     * the supplied code to link the chat to an application user.
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

        if (! preg_match('/^\/(?:start|pair)(?:@\w+)?(?:\s+(?<code>\S+))?/i', trim($text), $matches)) {
            return response()->json(['ok' => true]);
        }

        $code = $matches['code'] ?? null;

        $this->reply($chatId, $this->handlePairing($code, $chatId, $username));

        return response()->json(['ok' => true]);
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
