<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AirtableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function __construct(
        private AirtableService $airtable,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // Extract record ID from payload
        $payload = $request->json()->all();
        $recordId = (string) ($payload['record_id'] ?? '');

        if ($recordId === '') {
            Log::warning('Webhook: no record_id in payload', $payload);

            return response()->json(['ok' => true]);
        }

        // Fetch record from Airtable
        $record = $this->airtable->getUpdateRecord($recordId);

        $linkedTeamIds = $record['fields']['👥 გუნდები'] ?? [];

        if (empty($linkedTeamIds)) {
            Log::warning('Webhook: no team linked in record', ['record_id' => $recordId]);

            return response()->json(['ok' => true]);
        }

        // Fetch team name from გუნდები table
        $teamRecord = $this->airtable->getTeamRecord($linkedTeamIds[0]);
        $teamName = $teamRecord['fields']['👤 სახელი'] ?? null;

        if (! $teamName) {
            Log::warning('Webhook: no team name in record', ['record_id' => $recordId]);

            return response()->json(['ok' => true]);
        }

        // Only handle inspector teams
        $inspectorTeams = array_keys(config('vigil.shared_views.updates'));
        if (! in_array($teamName, $inspectorTeams)) {
            return response()->json(['ok' => true]);
        }

        // Check if already scheduled today
        $cacheKey = 'update_reminder_'.date('Y-m-d').'_'.md5($teamName);

        if (! Cache::has($cacheKey)) {
            Cache::put($cacheKey, [
                'team' => $teamName,
                'scheduled_at' => now()->toIso8601String(),
            ], now()->endOfDay());

            Log::info("Webhook: scheduled update reminder for {$teamName}");
        }

        return response()->json(['ok' => true]);
    }
}
