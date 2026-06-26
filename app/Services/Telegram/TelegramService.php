<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    /**
     * Whether a bot token has been configured.
     */
    public function isConfigured(): bool
    {
        return filled($this->token());
    }

    /**
     * Send a plain-text message to a Telegram chat.
     *
     * @throws ConnectionException
     * @throws RuntimeException when the bot token is not configured
     */
    public function sendMessage(string $chatId, string $text): Response
    {
        return $this->post('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * Register the URL Telegram should deliver updates to.
     *
     * @throws ConnectionException
     */
    public function setWebhook(string $url, ?string $secretToken = null): Response
    {
        return $this->post('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => ['message'],
        ]));
    }

    /**
     * @throws ConnectionException
     */
    public function deleteWebhook(): Response
    {
        return $this->post('deleteWebhook', []);
    }

    /**
     * @throws ConnectionException
     */
    public function getWebhookInfo(): Response
    {
        return $this->post('getWebhookInfo', []);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ConnectionException
     * @throws RuntimeException when the bot token is not configured
     */
    protected function post(string $method, array $payload): Response
    {
        $token = $this->token();

        if (blank($token)) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        return Http::asJson()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
    }

    protected function token(): ?string
    {
        $token = config('services.telegram.bot_token');

        return is_string($token) ? $token : null;
    }
}
