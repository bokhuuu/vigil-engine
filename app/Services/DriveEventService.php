<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DriveEventService
{
    public function __construct(
        private AirtableService $airtable,
    ) {}

    /**
     * A tracked subfolder received files — tick its Airtable checkbox.
     */
    public function fileAdded(string $actNumber, string $subfolderName): void
    {
        $tracked = config('vigil.drive.tracked_subfolders');
        $field = $tracked[$subfolderName] ?? null;

        if ($field === null) {
            Log::info("DriveEventService: [{$subfolderName}] has no checkbox — skipping tick");

            return;
        }

        $act = $this->airtable->getActByNumber($actNumber);

        if (! $act) {
            Log::warning("DriveEventService: ACT [{$actNumber}] not found in Airtable (added)");

            return;
        }

        $this->airtable->tickActCheckbox($act['id'], $field);
        Log::info("DriveEventService: ticked [{$field}] on ACT [{$actNumber}]");
    }

    /**
     * A tracked subfolder went empty — untick its Airtable checkbox.
     */
    public function fileRemoved(string $actNumber, string $subfolderName): void
    {
        $tracked = config('vigil.drive.tracked_subfolders');
        $field = $tracked[$subfolderName] ?? null;

        $act = $this->airtable->getActByNumber($actNumber);

        if (! $act) {
            Log::warning("DriveEventService: ACT [{$actNumber}] not found in Airtable (removed)");

            return;
        }

        if ($field !== null) {
            $this->airtable->untickActCheckbox($act['id'], $field);
            Log::info("DriveEventService: unticked [{$field}] on ACT [{$actNumber}]");
        }
    }
}
