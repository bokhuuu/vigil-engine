<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $botToken;

    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('vigil.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send a message to a Telegram chat.
     */
    public function send(string $chatId, string $message): bool
    {
        $response = Http::post("{$this->apiUrl}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ]);

        if ($response->failed()) {
            Log::error('Telegram send failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send message to Zura (owner) directly.
     */
    public function sendToMe(string $message): bool
    {
        return $this->send(config('vigil.telegram.my_chat_id'), $message);
    }

    /**
     * Send message to the Vigil team group chat.
     */
    public function sendToGroup(string $message): bool
    {
        return $this->send(config('vigil.telegram.group_chat_id'), $message);
    }
}
