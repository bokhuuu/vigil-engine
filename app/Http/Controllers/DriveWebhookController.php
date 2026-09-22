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
    /*
    |--------------------------------------------------------------------------
    | Subfolder → Airtable checkbox mapping
    |--------------------------------------------------------------------------
    |
    | ფოტოები and სკანი are intentionally excluded — no Airtable checkbox.
    | Only document folders trigger checkbox updates.
    |
    */
    private array $folderToField = [
        'ფოტოები' => '📥 ფოტოები',
        'შიდა ნახაზები' => '📥 შიდა ნახაზები',
        'გამიჯვნები' => '📥 გამიჯვნები',
        'ხარჯთაღრიცხვა' => '📥 ხარჯთაღრიცხვა',
        'ტოპო ნარგავები' => '📥 ტოპო ნარგავები',
    ];

    public function handle(Request $request): JsonResponse
    {
        // Verify secret
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

        // Ignore deletions
        if ($changeType === 'removed') {
            return response()->json(['ok' => true]);
        }

        // Check if this subfolder maps to an Airtable checkbox
        if (! isset($this->folderToField[$subfolder])) {
            Log::info("Drive webhook: no checkbox mapping for subfolder [{$subfolder}] — skipping");

            return response()->json(['ok' => true]);
        }

        $field = $this->folderToField[$subfolder];

        // Find the ACT record in Airtable by act number
        $airtable = new AirtableService(new AirtableClient);
        $act = $airtable->getActByNumber($actNumber);

        if (! $act) {
            Log::warning("Drive webhook: ACT [{$actNumber}] not found in Airtable");

            return response()->json(['ok' => true]);
        }

        $recordId = $act['id'];

        // Tick the checkbox
        $airtable->tickActCheckbox($recordId, $field);

        Log::info("Drive webhook: ticked [{$field}] on ACT [{$actNumber}]");

        return response()->json(['ok' => true]);
    }
}
