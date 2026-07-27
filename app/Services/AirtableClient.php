<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirtableClient
{
    private string $baseUrl;
    private string $baseId;
    private string $token;

    public function __construct()
    {
        $this->token   = config('vigil.airtable.token');
        $this->baseId  = config('vigil.airtable.base_id');
        $this->baseUrl = config('vigil.airtable.base_url');
    }

    /**
     * Fetch all records from a table, handling pagination automatically.
     */
    public function get(string $tableId, array $params = []): array
    {
        $records = [];
        $offset  = null;

        do {
            if ($offset) {
                $params['offset'] = $offset;
            }

            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/{$this->baseId}/{$tableId}", $params);

            if ($response->failed()) {
                Log::error('Airtable GET failed', [
                    'table'  => $tableId,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                $this->notifyFailure('GET', $tableId, $response->status());
                break;
            }

            $data    = $response->json();
            $records = array_merge($records, $data['records'] ?? []);
            $offset  = $data['offset'] ?? null;
        } while ($offset);

        return $records;
    }

    /**
     * Update an existing record (PATCH — only changed fields).
     */
    public function patch(string $tableId, string $recordId, array $fields): array
    {
        $response = Http::withToken($this->token)
            ->patch("{$this->baseUrl}/{$this->baseId}/{$tableId}/{$recordId}", [
                'fields' => $fields,
            ]);

        if ($response->failed()) {
            Log::error('Airtable PATCH failed', [
                'table'  => $tableId,
                'record' => $recordId,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            $this->notifyFailure('PATCH', $tableId, $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Create a new record.
     */
    public function post(string $tableId, array $fields): array
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/{$this->baseId}/{$tableId}", [
                'records' => [
                    ['fields' => $fields],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Airtable POST failed', [
                'table'  => $tableId,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            $this->notifyFailure('POST', $tableId, $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Send Telegram alert when Airtable call fails.
     */
    private function notifyFailure(string $method, string $tableId, int $status): void
    {
        try {
            (new TelegramService())->sendToMe(
                "⚠️ <b>Vigil — Airtable შეცდომა</b>\n"
                    . "მეთოდი: {$method}\n"
                    . "ტაბელი: {$tableId}\n"
                    . "სტატუსი: {$status}\n"
                    . "დრო: " . now()->format('d.m.Y H:i')
            );
        } catch (\Throwable $e) {
            Log::error('Telegram failure alert failed', ['error' => $e->getMessage()]);
        }
    }
}
