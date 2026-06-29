<?php

use App\Models\User;
use App\Services\Telegram\TelegramPairingService;
use Illuminate\Support\Facades\Http;

function telegramUpdate(string $text, int|string $chatId = 4242, ?string $username = 'janedoe'): array
{
    return [
        'update_id' => 1,
        'message' => [
            'message_id' => 1,
            'from' => array_filter(['id' => $chatId, 'username' => $username], fn ($v) => $v !== null),
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'text' => $text,
        ],
    ];
}

beforeEach(function () {
    config()->set('services.telegram.bot_token', 'test-token');
    config()->set('services.telegram.webhook_secret', null);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
});

it('links the user and replies when a valid /pair code arrives', function () {
    $user = User::factory()->create(['name' => 'Jane']);
    $code = app(TelegramPairingService::class)->generateCodeFor($user);

    $this->postJson(route('telegram.webhook'), telegramUpdate("/pair {$code}", 4242, 'janedoe'))
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($user->fresh())
        ->telegram_chat_id->toBe('4242')
        ->telegram_username->toBe('janedoe');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage')
        && $request['chat_id'] === '4242'
        && str_contains($request['text'], 'Connected'));
});

it('also accepts the /start <code> deep-link form', function () {
    $user = User::factory()->create();
    $code = app(TelegramPairingService::class)->generateCodeFor($user);

    $this->postJson(route('telegram.webhook'), telegramUpdate("/start {$code}", 777))
        ->assertOk();

    expect($user->fresh()->telegram_chat_id)->toBe('777');
});

it('replies with an error and does not link on an invalid code', function () {
    $this->postJson(route('telegram.webhook'), telegramUpdate('/pair WRONG1', 999))
        ->assertOk();

    expect(User::where('telegram_chat_id', '999')->exists())->toBeFalse();

    Http::assertSent(fn ($request): bool => str_contains($request['text'], 'invalid or has expired'));
});

it('greets when /start arrives without a code', function () {
    $this->postJson(route('telegram.webhook'), telegramUpdate('/start'))
        ->assertOk();

    Http::assertSent(fn ($request): bool => str_contains($request['text'], 'pairing code'));
});

it('rejects updates with a missing or wrong secret token when one is configured', function () {
    config()->set('services.telegram.webhook_secret', 's3cr3t');

    $this->postJson(route('telegram.webhook'), telegramUpdate('/start'))
        ->assertForbidden();

    $this->postJson(route('telegram.webhook'), telegramUpdate('/start'), [
        'X-Telegram-Bot-Api-Secret-Token' => 's3cr3t',
    ])->assertOk();
});

it('prompts unpaired chats to connect when they send a non-command message', function () {
    $this->postJson(route('telegram.webhook'), telegramUpdate('hello there'))
        ->assertOk();

    Http::assertSent(fn ($request): bool => str_contains($request['text'], '/pair YOURCODE'));
});
