<?php

use App\Models\User;

it('shows the telegram settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('telegram.edit'))
        ->assertOk();
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
