<?php

declare(strict_types=1);

use App\Services\ManagerNotifier;
use Illuminate\Support\Facades\Http;

test('notify sends to me and every manager with a Telegram ID', function () {
    config([
        'vigil.telegram.bot_token' => 'test-token',
        'vigil.telegram.my_chat_id' => '999',
    ]);

    Http::fake([
        'api.airtable.com/*' => Http::response([
            'records' => [
                ['id' => 'r1', 'fields' => ['🏷️ როლი' => 'მენეჯერი', '✈️ Telegram' => '111']],
                ['id' => 'r2', 'fields' => ['🏷️ როლი' => 'მენეჯერი']],
                ['id' => 'r3', 'fields' => ['🏷️ როლი' => 'ინსპექტორი', '✈️ Telegram' => '333']],
            ],
        ], 200),
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    app(ManagerNotifier::class)->notify('ტესტი');

    $toChat = fn (string $id) => fn ($request) => str_contains($request->url(), 'api.telegram.org')
        && $request['chat_id'] === $id;

    Http::assertSent($toChat('999'));      // me
    Http::assertSent($toChat('111'));      // manager with Telegram
    Http::assertNotSent($toChat('333'));   // inspector — never messaged
});
