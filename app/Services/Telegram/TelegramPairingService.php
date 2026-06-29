<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TelegramPairingService
{
    /**
     * Generate (and persist) a fresh pairing code for the user.
     */
    public function generateCodeFor(User $user): string
    {
        $code = $this->uniqueCode();

        $user->forceFill([
            'telegram_pair_code' => $code,
            'telegram_pair_code_expires_at' => now()->addMinutes($this->ttlMinutes()),
        ])->save();

        return $code;
    }

    /**
     * Redeem a pairing code sent from Telegram, linking the chat to its user.
     *
     * Returns the linked user, or null when the code is unknown or expired.
     */
    public function redeem(string $code, string $chatId, ?string $username): ?User
    {
        $user = User::query()
            ->where('telegram_pair_code', $this->normalize($code))
            ->where(function (Builder $query): void {
                $query->whereNull('telegram_pair_code_expires_at')
                    ->orWhere('telegram_pair_code_expires_at', '>', now());
            })
            ->first();

        if ($user === null) {
            return null;
        }

        // A Telegram account may only be linked to one user at a time, so detach
        // it from any other user before binding it here.
        User::query()
            ->where('telegram_chat_id', $chatId)
            ->whereKeyNot($user->getKey())
            ->update([
                'telegram_chat_id' => null,
                'telegram_username' => null,
                'telegram_connected_at' => null,
            ]);

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_connected_at' => now(),
            'telegram_pair_code' => null,
            'telegram_pair_code_expires_at' => null,
        ])->save();

        return $user;
    }

    /**
     * Unlink the user's Telegram account.
     */
    public function disconnect(User $user): void
    {
        $user->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_connected_at' => null,
            'telegram_pair_code' => null,
            'telegram_pair_code_expires_at' => null,
        ])->save();
    }

    protected function uniqueCode(): string
    {
        do {
            $code = $this->normalize(Str::random(6));
        } while (User::where('telegram_pair_code', $code)->exists());

        return $code;
    }

    protected function normalize(string $code): string
    {
        return Str::upper(trim($code));
    }

    protected function ttlMinutes(): int
    {
        return (int) config('services.telegram.pair_code_ttl', 15);
    }
}
