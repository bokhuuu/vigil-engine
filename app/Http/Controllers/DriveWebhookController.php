<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AirtableService;
use App\Services\DriveEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DriveWebhookController
{
    public function __construct(
        private AirtableService $airtable,
        private DriveEventService $events,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $actNumber = (string) ($payload['act_number'] ?? '');
        $subfolder = (string) ($payload['subfolder'] ?? '');
        $changeType = $payload['change_type'] ?? 'added';

        if ($actNumber === '' || $subfolder === '') {
            Log::warning('Drive webhook: missing act_number or subfolder', $payload);

            return response()->json(['ok' => true]);
        }

        $tracked = config('vigil.drive.tracked_subfolders');

        if (! array_key_exists($subfolder, $tracked)) {
            Log::info("Drive webhook: untracked subfolder [{$subfolder}] — skipping");

            return response()->json(['ok' => true]);
        }

        if ($changeType === 'removed') {
            $this->events->fileRemoved($actNumber, $subfolder);

            return response()->json(['ok' => true]);
        }

        $this->events->fileAdded($actNumber, $subfolder);

        return response()->json(['ok' => true]);
    }

    /**
     * GET /webhook/act-statuses
     * Signature status of every ACT in one response, cached briefly.
     */
    public function actStatuses(Request $request): JsonResponse
    {
        $statuses = Cache::remember(
            'drive_act_statuses',
            (int) config('vigil.drive.statuses_cache_seconds'),
            fn () => $this->airtable->getSignatureStatuses()
        );

        return response()->json(['statuses' => (object) $statuses]);
    }
}
