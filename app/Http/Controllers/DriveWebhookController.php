<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriveWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $secret = $request->query('secret');

        if ($secret !== config('vigil.webhook.secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->json()->all();
        $actNumber = $payload['act_number'] ?? null;
        $subfolder = $payload['subfolder'] ?? null;
        $changeType = $payload['change_type'] ?? 'added';

        if (! $actNumber || ! $subfolder) {
            Log::warning('Drive webhook: missing act_number or subfolder', $payload);

            return response()->json(['ok' => true]);
        }

        if ($changeType === 'removed') {
            return response()->json(['ok' => true]);
        }

        $tracked = config('vigil.drive.tracked_subfolders');

        if (! array_key_exists($subfolder, $tracked)) {
            Log::info("Drive webhook: untracked subfolder [{$subfolder}] — skipping");

            return response()->json(['ok' => true]);
        }

        $field = $tracked[$subfolder];

        if ($field === null) {
            Log::info("Drive webhook: [{$subfolder}] has no checkbox — skipping");

            return response()->json(['ok' => true]);
        }

        $airtable = new AirtableService(new AirtableClient);
        $act = $airtable->getActByNumber($actNumber);

        if (! $act) {
            Log::warning("Drive webhook: ACT [{$actNumber}] not found in Airtable");

            return response()->json(['ok' => true]);
        }

        $airtable->tickActCheckbox($act['id'], $field);

        Log::info("Drive webhook: ticked [{$field}] on ACT [{$actNumber}]");

        return response()->json(['ok' => true]);
    }

    /**
     * GET /webhook/act-status?act_number=329&secret=...
     * Returns whether ხელმოწერები is ticked for the given ACT.
     * Used by Apps Script to determine root folder color.
     */
    public function actStatus(Request $request): JsonResponse
    {
        $secret = $request->query('secret');

        if ($secret !== config('vigil.webhook.secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $actNumber = $request->query('act_number');

        if (! $actNumber) {
            return response()->json(['error' => 'Missing act_number'], 400);
        }

        $airtable = new AirtableService(new AirtableClient);
        $act = $airtable->getActByNumber($actNumber);

        if (! $act) {
            return response()->json(['error' => 'ACT not found'], 404);
        }

        $fields = $act['fields'];

        $signatures = ! empty($fields['📥 ხელმოწერები']);

        return response()->json([
            'act_number' => $actNumber,
            'signatures' => $signatures,
        ]);
    }
}
