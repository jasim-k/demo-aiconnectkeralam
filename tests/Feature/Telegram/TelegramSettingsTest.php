<?php

use App\Models\User;
use App\Services\Telegram\TelegramPairingService;
use Inertia\Testing\AssertableInertia;

it('shows the telegram settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('telegram.edit'))
        ->assertOk();
});

it('exposes a deep link and QR code while a pairing code is active', function () {
    config()->set('services.telegram.bot_username', '@AiConnectStoreBot');

    $user = User::factory()->create();
    $code = app(TelegramPairingService::class)->generateCodeFor($user);

    $this->actingAs($user)
        ->get(route('telegram.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('telegram.pair_deep_link', "https://t.me/AiConnectStoreBot?start={$code}")
            ->where('telegram.pair_qr_svg', fn (?string $uri): bool => is_string($uri)
                && str_starts_with($uri, 'data:image/svg+xml;base64,')));
});

it('omits the deep link and QR code once connected', function () {
    config()->set('services.telegram.bot_username', '@AiConnectStoreBot');

    $user = User::factory()->create();
    $user->forceFill([
        'telegram_chat_id' => '321',
        'telegram_connected_at' => now(),
    ])->save();

    $this->actingAs($user)
        ->get(route('telegram.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('telegram.pair_deep_link', null)
            ->where('telegram.pair_qr_svg', null));
});

it('generates a pairing code for the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('telegram.connect'))
        ->assertRedirect(route('telegram.edit'));

    expect($user->fresh()->telegram_pair_code)->not->toBeNull();
});

it('disconnects the telegram account', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'telegram_chat_id' => '321',
        'telegram_username' => 'jane',
        'telegram_connected_at' => now(),
    ])->save();

    $this->actingAs($user)
        ->delete(route('telegram.disconnect'))
        ->assertRedirect(route('telegram.edit'));

    expect($user->fresh()->hasConnectedTelegram())->toBeFalse();
});

it('requires authentication', function () {
    $this->get(route('telegram.edit'))->assertRedirect(route('login'));
});
