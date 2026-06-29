<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

// Telegram bot updates. Telegram calls this endpoint; it is authenticated by the
// shared secret token header rather than a session or bearer token.
Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');
