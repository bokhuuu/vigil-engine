<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AirtableService;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardFolderController
{
    public function __construct(
        private AirtableService $airtable,
        private GoogleDriveService $drive,
    ) {}

    /**
     * Create the Drive folder tree for one ACT right now (dashboard button).
     */
    public function store(string $recordId): RedirectResponse
    {
        $act = $this->airtable->getAct($recordId);
        $fields = $act['fields'] ?? [];
        $number = (string) ($fields['🔖 აქტის ნომერი'] ?? $recordId);

        if (! empty($fields['💼 საქაღალდე'])) {
            return back()->with('status', "ACT {$number}-ს საქაღალდე უკვე აქვს.");
        }

        try {
            $folderUrl = $this->drive->ensureActFolders($number, $fields);
            $this->airtable->updateActDriveFolder($recordId, $folderUrl);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "ACT {$number}: საქაღალდე ვერ შეიქმნა — {$e->getMessage()}");
        }

        Cache::forget(DashboardController::SNAPSHOT_KEY);
        Log::info("Dashboard: created Drive folder for ACT [{$number}]");

        return back()->with('status', "✅ ACT {$number} — საქაღალდე შეიქმნა.");
    }
}
