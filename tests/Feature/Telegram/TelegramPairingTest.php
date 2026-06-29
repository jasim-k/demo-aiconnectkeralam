<?php

use App\Models\User;
use App\Services\Telegram\TelegramPairingService;

beforeEach(function () {
    $this->pairing = app(TelegramPairingService::class);
});

it('generates and persists a pairing code for a user', function () {
    $user = User::factory()->create();

    $code = $this->pairing->generateCodeFor($user);

    expect($code)->toBeString()->not->toBeEmpty();
    expect($user->fresh())
        ->telegram_pair_code->toBe($code)
        ->telegram_pair_code_expires_at->not->toBeNull();
});

it('links a telegram chat to the user when a valid code is redeemed', function () {
    $user = User::factory()->create();
    $code = $this->pairing->generateCodeFor($user);

    $linked = $this->pairing->redeem($code, '987654', 'janedoe');

    expect($linked?->id)->toBe($user->id);
    expect($user->fresh())
        ->telegram_chat_id->toBe('987654')
        ->telegram_username->toBe('janedoe')
        ->telegram_connected_at->not->toBeNull()
        ->telegram_pair_code->toBeNull()
        ->hasConnectedTelegram()->toBeTrue();
});

it('redeems case-insensitively', function () {
    $user = User::factory()->create();
    $code = $this->pairing->generateCodeFor($user);

    expect($this->pairing->redeem(strtolower($code), '111', null)?->id)->toBe($user->id);
});

it('rejects an unknown code', function () {
    expect($this->pairing->redeem('NOPE12', '111', null))->toBeNull();
});

it('rejects an expired code', function () {
    $user = User::factory()->create();
    $code = $this->pairing->generateCodeFor($user);
    $user->forceFill(['telegram_pair_code_expires_at' => now()->subMinute()])->save();

    expect($this->pairing->redeem($code, '111', null))->toBeNull();
    expect($user->fresh()->hasConnectedTelegram())->toBeFalse();
});

it('moves a telegram chat from one user to another when re-paired', function () {
    $first = User::factory()->create();
    $this->pairing->redeem($this->pairing->generateCodeFor($first), '5000', 'jane');

    $second = User::factory()->create();
    $this->pairing->redeem($this->pairing->generateCodeFor($second), '5000', 'jane');

    expect($first->fresh()->telegram_chat_id)->toBeNull();
    expect($second->fresh()->telegram_chat_id)->toBe('5000');
});

it('disconnects a linked user', function () {
    $user = User::factory()->create();
    $this->pairing->redeem($this->pairing->generateCodeFor($user), '5000', 'jane');

    $this->pairing->disconnect($user);

    expect($user->fresh()->hasConnectedTelegram())->toBeFalse();
});
