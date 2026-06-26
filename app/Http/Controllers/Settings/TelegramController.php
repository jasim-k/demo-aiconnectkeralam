<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramPairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TelegramController extends Controller
{
    public function __construct(private TelegramPairingService $pairing) {}

    /**
     * Show the Telegram connection settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/telegram', [
            'telegram' => [
                'connected' => $user->hasConnectedTelegram(),
                'username' => $user->telegram_username,
                'connected_at' => $user->telegram_connected_at?->toIso8601String(),
                'pair_code' => $user->hasConnectedTelegram() ? null : $user->telegram_pair_code,
                'pair_code_expires_at' => $user->telegram_pair_code_expires_at?->toIso8601String(),
            ],
            'botUsername' => config('services.telegram.bot_username'),
        ]);
    }

    /**
     * Generate (or refresh) the user's pairing code.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->pairing->generateCodeFor($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pairing code generated.')]);

        return to_route('telegram.edit');
    }

    /**
     * Unlink the user's Telegram account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->pairing->disconnect($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Telegram disconnected.')]);

        return to_route('telegram.edit');
    }
}
