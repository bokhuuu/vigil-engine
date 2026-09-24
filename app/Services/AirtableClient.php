<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AirtableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirtableClient
{
    private string $baseUrl;

    private string $baseId;

    private string $token;

    public function __construct()
    {
        $this->token = config('vigil.airtable.token');
        $this->baseId = config('vigil.airtable.base_id');
        $this->baseUrl = config('vigil.airtable.base_url');
    }

    /**
     * Fetch all records from a table, handling pagination automatically.
     * Retries temporary errors; throws instead of returning partial data.
     *
     * @throws AirtableException
     */
    public function get(string $tableId, array $params = []): array
    {
        $records = [];
        $offset = null;

        do {
            if ($offset) {
                $params['offset'] = $offset;
            }

            try {
                $response = $this->request()
                    ->get("{$this->baseUrl}/{$this->baseId}/{$tableId}", $params);
            } catch (ConnectionException $e) {
                $this->fail('GET', $tableId, 0, $e->getMessage());
            }

            if ($response->failed()) {
                $this->fail('GET', $tableId, $response->status(), $response->body());
            }

            $data = $response->json();
            $records = array_merge($records, $data['records'] ?? []);
            $offset = $data['offset'] ?? null;
        } while ($offset);

        return $records;
    }

    /**
     * Fetch a single record by ID.
     *
     * @throws AirtableException
     */
    public function getRecord(string $tableId, string $recordId): array
    {
        try {
            $response = $this->request()
                ->get("{$this->baseUrl}/{$this->baseId}/{$tableId}/{$recordId}");
        } catch (ConnectionException $e) {
            $this->fail('GET record', $tableId, 0, $e->getMessage());
        }

        if ($response->failed()) {
            $this->fail('GET record', $tableId, $response->status(), $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Update an existing record (PATCH — only changed fields).
     * Safe to retry: setting the same values twice gives the same result.
     *
     * @throws AirtableException
     */
    public function patch(string $tableId, string $recordId, array $fields): array
    {
        try {
            $response = $this->request()
                ->patch("{$this->baseUrl}/{$this->baseId}/{$tableId}/{$recordId}", [
                    'fields' => $fields,
                ]);
        } catch (ConnectionException $e) {
            $this->fail('PATCH', $tableId, 0, $e->getMessage());
        }

        if ($response->failed()) {
            $this->fail('PATCH', $tableId, $response->status(), $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Create a new record.
     * NOT retried: if Airtable saved it but the reply was lost,
     * a retry would create a duplicate.
     *
     * @throws AirtableException
     */
    public function post(string $tableId, array $fields): array
    {
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/{$this->baseId}/{$tableId}", [
                    'records' => [
                        ['fields' => $fields],
                    ],
                ]);
        } catch (ConnectionException $e) {
            $this->fail('POST', $tableId, 0, $e->getMessage());
        }

        if ($response->failed()) {
            $this->fail('POST', $tableId, $response->status(), $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * HTTP client with retry on temporary failures (5xx, 429, timeouts).
     */
    private function request(): PendingRequest
    {
        return Http::withToken($this->token)->retry(
            (int) config('vigil.airtable.retry_times'),
            (int) config('vigil.airtable.retry_sleep_ms'),
            fn (\Throwable $e) => $e instanceof ConnectionException
                || ($e instanceof RequestException
                    && ($e->response->serverError() || $e->response->status() === 429)),
            throw: false,
        );
    }

    /**
     * Log, alert Zura, and throw — never let callers continue with bad data.
     *
     * @throws AirtableException
     */
    private function fail(string $method, string $tableId, int $status, string $body): never
    {
        Log::error("Airtable {$method} failed", [
            'table' => $tableId,
            'status' => $status,
            'body' => $body,
        ]);

        $this->notifyFailure($method, $tableId, $status);

        throw new AirtableException("Airtable {$method} {$tableId} failed with status {$status}");
    }

    /**
     * Send Telegram alert when Airtable call fails.
     */
    private function notifyFailure(string $method, string $tableId, int $status): void
    {
        try {
            (new TelegramService)->sendToMe(
                "⚠️ <b>Vigil — Airtable შეცდომა</b>\n"
                    ."მეთოდი: {$method}\n"
                    ."ტაბელი: {$tableId}\n"
                    ."სტატუსი: {$status}\n"
                    .'დრო: '.now()->format('d.m.Y H:i')
            );
        } catch (\Throwable $e) {
            Log::error('Telegram failure alert failed', ['error' => $e->getMessage()]);
        }
    }
}
