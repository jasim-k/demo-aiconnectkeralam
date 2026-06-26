<?php

namespace App\Services\Telegram;

use App\Ai\Agents\TelegramShopAssistant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Drives the conversational shopping assistant for a paired Telegram customer.
 */
class TelegramAssistantService
{
    /**
     * Whether the assistant has an AI provider configured.
     */
    public function isEnabled(): bool
    {
        return filled(config('ai.providers.openai.key'));
    }

    /**
     * Handle one incoming customer message and return the assistant's reply.
     *
     * The paired customer is authenticated for the duration of the prompt so the
     * reused MCP tools resolve their cart and orders. Conversation context is
     * carried across messages via the customer's most recent conversation.
     */
    public function reply(User $user, string $message): string
    {
        Auth::setUser($user);

        $response = (new TelegramShopAssistant($user))
            ->continueLastConversation($user)
            ->prompt($message);

        return $response->text;
    }
}
