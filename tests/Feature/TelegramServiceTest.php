<?php

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;

test('sendToMe sends message to my chat id', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    $telegram = new TelegramService;
    $result = $telegram->sendToMe('ტესტი');

    expect($result)->toBeTrue();
    Http::assertSent(
        fn ($request) => str_contains($request->url(), config('vigil.telegram.bot_token')) &&
            $request['chat_id'] == config('vigil.telegram.my_chat_id') &&
            $request['text'] === 'ტესტი'
    );
});

test('sendToGroup sends message to group chat id', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    $telegram = new TelegramService;
    $result = $telegram->sendToGroup('გუნდის შეტყობინება');

    expect($result)->toBeTrue();
    Http::assertSent(
        fn ($request) => $request['chat_id'] == config('vigil.telegram.group_chat_id')
    );
});

test('send returns false on failure', function () {
    Http::fake([
        '*' => Http::response(['ok' => false], 400),
    ]);

    $telegram = new TelegramService;
    $result = $telegram->send('123456', 'ტესტი');

    expect($result)->toBeFalse();
});
