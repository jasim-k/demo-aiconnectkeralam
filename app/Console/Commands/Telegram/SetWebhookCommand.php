<?php

namespace App\Console\Commands\Telegram;

use App\Services\Telegram\TelegramService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

#[Signature('telegram:set-webhook
    {url? : The public webhook URL (defaults to the telegram.webhook route)}
    {--delete : Remove the webhook instead of registering it}
    {--info : Show the current webhook info and exit}')]
#[Description('Register (or remove) the Telegram bot webhook with the Bot API')]
class SetWebhookCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        try {
            if ($this->option('info')) {
                $this->line($telegram->getWebhookInfo()->body());

                return self::SUCCESS;
            }

            if ($this->option('delete')) {
                $telegram->deleteWebhook();
                $this->components->info('Webhook deleted.');

                return self::SUCCESS;
            }

            $url = $this->argument('url') ?? route('telegram.webhook');
            $secret = config('services.telegram.webhook_secret');

            if (blank($secret)) {
                $this->components->warn('TELEGRAM_WEBHOOK_SECRET is not set; the webhook will accept unauthenticated requests.');
            }

            $response = $telegram->setWebhook($url, is_string($secret) ? $secret : null);

            if ($response->json('ok') === true) {
                $this->components->info("Webhook registered: {$url}");

                return self::SUCCESS;
            }

            $this->components->error('Telegram rejected the webhook: '.$response->body());

            return self::FAILURE;
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } catch (ConnectionException $e) {
            $this->components->error('Could not reach Telegram: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
