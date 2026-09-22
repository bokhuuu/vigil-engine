<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\GoogleDriveService;
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

        if (! isset($this->folderToField[$subfolder])) {
            Log::info("Drive webhook: no checkbox mapping for subfolder [{$subfolder}] — skipping");

            return response()->json(['ok' => true]);
        }

        $field = $this->folderToField[$subfolder];

        $airtable = new AirtableService(new AirtableClient);
        $act = $airtable->getActByNumber($actNumber);

        if (! $act) {
            Log::warning("Drive webhook: ACT [{$actNumber}] not found in Airtable");

            return response()->json(['ok' => true]);
        }

        $recordId = $act['id'];
        $fields = $act['fields'];

        // Tick the checkbox
        $airtable->tickActCheckbox($recordId, $field);

        Log::info("Drive webhook: ticked [{$field}] on ACT [{$actNumber}]");

        // Update Drive folder color based on completeness
        $this->updateFolderColor($actNumber, $fields, $field, $airtable);

        return response()->json(['ok' => true]);
    }

    /**
     * Update the ACT root folder color based on document completeness.
     * Green = all required docs received, Yellow = some missing.
     */
    private function updateFolderColor(string $actNumber, array $fields, string $justTicked, AirtableService $airtable): void
    {
        $folderUrl = $fields['💼 საქაღალდე'] ?? null;

        if (! $folderUrl) {
            return;
        }

        // Extract folder ID from URL
        preg_match('/folders\/([a-zA-Z0-9_-]+)/', $folderUrl, $matches);
        $folderId = $matches[1] ?? null;

        if (! $folderId) {
            return;
        }

        // Determine required fields for this ACT
        $required = $this->requiredDocFields($fields);

        // Merge the just-ticked field as true (Airtable not yet updated)
        $fields[$justTicked] = true;

        // Check if all required docs are now received
        $allReceived = collect($required)->every(
            fn ($f) => ! empty($fields[$f])
        );

        $color = $allReceived ? '#00843D' : '#F6C026';

        try {
            (new GoogleDriveService)->setFolderColor($folderId, $color);
            Log::info('Drive webhook: folder color set to '.($allReceived ? 'green' : 'yellow')." for ACT [{$actNumber}]");
        } catch (\Throwable $e) {
            Log::error("Drive webhook: failed to set folder color for ACT [{$actNumber}]", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Determine which doc fields are required for this ACT.
     * Mirrors GoogleDriveService and DashboardController logic.
     */
    private function requiredDocFields(array $fields): array
    {
        $required = [
            '📥 ფოტოები',
            '📥 ხელმოწერები',
        ];

        if ($fields['🏠 შენობა/ნაგებობები'] ?? false) {
            $required[] = '📥 შიდა ნახაზები';
        }

        if ($fields['📋 გამიჯვნა საჭიროა'] ?? false) {
            $required[] = '📥 გამიჯვნები';
        }

        $needsCost = ($fields['🏠 შენობა/ნაგებობები'] ?? false)
            || ($fields['🏠 ღობე - ჭიშკარი'] ?? false)
            || ($fields['🏠 გადასატანი აქტივები'] ?? false)
            || ($fields['🏠 ეზოს-მოპირკეთება'] ?? false)
            || ($fields['🏠 სამ-ფაზიანი დენი'] ?? false);

        if ($needsCost) {
            $required[] = '📥 ხარჯთაღრიცხვა';
        }

        if (($fields['🏠 მრავალწლოვანი'] ?? false) || ($fields['🏠 ერთწლოვანი'] ?? false)) {
            $required[] = '📥 ტოპო ნარგავები';
        }

        return $required;
    }
}
