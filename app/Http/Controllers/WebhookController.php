<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function handle(Request $request): JsonResponse
    {
        // Verify secret
        $secret = $request->header('X-Airtable-Hmac-SHA256')
            ?? $request->query('secret');

        if ($secret !== config('vigil.webhook.secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Extract team name from payload
        $payload = $request->json()->all();
        $teamField = config('vigil.airtable.fields.updates.team');
        $teamName = data_get($payload, "fields.{$teamField}.0.name");

        if (! $teamName) {
            Log::warning('Webhook: no team name in payload', $payload);

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
