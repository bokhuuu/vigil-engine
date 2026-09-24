<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AirtableService;
use App\Services\ManagerNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DriveWebhookController
{
    public function __construct(
        private AirtableService $airtable,
        private ManagerNotifier $notifier,
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

        $field = $tracked[$subfolder];

        if ($changeType === 'removed') {
            return $this->handleRemoved($actNumber, $subfolder, $field);
        }

        if ($field === null) {
            Log::info("Drive webhook: [{$subfolder}] has no checkbox — skipping");

            return response()->json(['ok' => true]);
        }

        $act = $this->airtable->getActByNumber($actNumber);

        if (! $act) {
            Log::warning("Drive webhook: ACT [{$actNumber}] not found in Airtable");

            return response()->json(['ok' => true]);
        }

        $this->airtable->tickActCheckbox($act['id'], $field);

        Log::info("Drive webhook: ticked [{$field}] on ACT [{$actNumber}]");

        return response()->json(['ok' => true]);
    }

    /**
     * Folder went empty (after Apps Script's grace period):
     * untick its checkbox if it has one, then alert Zura + managers.
     */
    private function handleRemoved(string $actNumber, string $subfolder, ?string $field): JsonResponse
    {
        $act = $this->airtable->getActByNumber($actNumber);

        if (! $act) {
            Log::warning("Drive webhook: ACT [{$actNumber}] not found in Airtable (removed)");

            return response()->json(['ok' => true]);
        }

        if ($field !== null) {
            $this->airtable->untickActCheckbox($act['id'], $field);
            Log::info("Drive webhook: unticked [{$field}] on ACT [{$actNumber}]");
        }

        $message = '⚠️ <b>ACT '.e($actNumber).'</b> — '.e($subfolder)." ცარიელია\n"
            .'ფაილები წაიშალა Drive-იდან.';

        $folderUrl = $act['fields']['💼 საქაღალდე'] ?? null;

        if ($folderUrl) {
            $message .= "\n🔗 <a href=\"".e($folderUrl).'">საქაღალდე</a>';
        }

        $this->notifier->notify($message);

        Log::info("Drive webhook: removal alert sent for [{$subfolder}] on ACT [{$actNumber}]");

        return response()->json(['ok' => true]);
    }

    /**
     * GET /webhook/act-statuses?secret=...
     * Signature status of every ACT in one response, cached briefly.
     * Used by Apps Script once per run instead of one call per ACT.
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
