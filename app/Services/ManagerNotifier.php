<?php

declare(strict_types=1);

namespace App\Services;

class ManagerNotifier
{
    public function __construct(
        private AirtableService $airtable,
        private TelegramService $telegram,
    ) {}

    /**
     * Send a message to Zura and every manager with a Telegram ID.
     */
    public function notify(string $message): void
    {
        $this->telegram->sendToMe($message);

        foreach ($this->airtable->getManagers() as $manager) {
            $telegramId = (string) ($manager['fields']['✈️ Telegram'] ?? '');

            if ($telegramId !== '') {
                $this->telegram->send($telegramId, $message);
            }
        }
    }
}
