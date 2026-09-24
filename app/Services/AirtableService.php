<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class AirtableService
{
    public function __construct(
        private AirtableClient $client
    ) {}

    /**
     * Build a direct Airtable record URL.
     */
    public function recordUrl(string $tableId, string $recordId): string
    {
        $baseId = config('vigil.airtable.base_id');

        return "https://airtable.com/{$baseId}/{$tableId}/{$recordId}";
    }

    /*
    |--------------------------------------------------------------------------
    | ACTS
    |--------------------------------------------------------------------------
    */

    public function getIncompleteActs(): array
    {
        return $this->client->get(config('vigil.airtable.tables.acts'), [
            'filterByFormula' => "{🚦სტატუსი} = '⏳ მიმდინარე'",
        ]);
    }

    /**
     * Get all completed ACTs.
     */
    public function getDoneActs(): array
    {
        return $this->client->get(config('vigil.airtable.tables.acts'), [
            'filterByFormula' => "{🚦სტატუსი} = '✅ დასრულებული'",
        ]);
    }

    public function getOverdueActs(): array
    {
        $cutoff = Carbon::now()->subDays(config('vigil.overdue_days'))->toDateString();

        return $this->client->get(config('vigil.airtable.tables.acts'), [
            'filterByFormula' => "AND({🚦სტატუსი} = '⏳ მიმდინარე', {📅 აქტის მიღების თარიღი} <= '{$cutoff}')",
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CORRESPONDENCE
    |--------------------------------------------------------------------------
    */

    public function getOverdueCorrespondence(): array
    {
        $today = Carbon::now()->toDateString();

        return $this->client->get(config('vigil.airtable.tables.correspondence'), [
            'filterByFormula' => "AND({⏰ ვადა} != '', {⏰ ვადა} < '{$today}', {🚦სტატუსი} != '📤 პასუხი გაგზავნილი', {🚦სტატუსი} != '🔴 ვადაგადაცილებული')",
        ]);
    }

    public function markCorrespondenceOverdue(string $recordId): void
    {
        $this->client->patch(config('vigil.airtable.tables.correspondence'), $recordId, [
            '🚦სტატუსი' => '🔴 ვადაგადაცილებული',
        ]);
    }

    /**
     * Get all correspondence already marked as overdue.
     */
    public function getAlreadyOverdueCorrespondence(): array
    {
        return $this->client->get(config('vigil.airtable.tables.correspondence'), [
            'filterByFormula' => "{🚦სტატუსი} = '🔴 ვადაგადაცილებული'",
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TEAMS
    |--------------------------------------------------------------------------
    */

    public function getTeams(): array
    {
        return $this->client->get(config('vigil.airtable.tables.teams'));
    }

    /**
     * Get all ACTs regardless of status.
     */
    public function getAllActs(): array
    {
        return $this->client->get(config('vigil.airtable.tables.acts'));
    }

    /**
     * Get ACTs that don't have a Drive folder yet.
     */
    public function getActsWithoutDriveFolder(?int $limit = null): array
    {
        $params = [
            'filterByFormula' => "{💼 საქაღალდე} = ''",
        ];

        if ($limit !== null) {
            $params['maxRecords'] = $limit;
        }

        return $this->client->get(config('vigil.airtable.tables.acts'), $params);
    }

    /**
     * ACTs that already have a Drive folder and were modified recently —
     * candidates for creating missing subfolders.
     */
    public function getRecentlyChangedActsWithFolder(int $minutes): array
    {
        return $this->client->get(config('vigil.airtable.tables.acts'), [
            'filterByFormula' => "AND({💼 საქაღალდე} != '', "
                ."IS_AFTER(LAST_MODIFIED_TIME(), DATEADD(NOW(), -{$minutes}, 'minutes')))",
        ]);
    }

    /**
     * Write the Drive folder URL back to the ACT record.
     */
    public function updateActDriveFolder(string $recordId, string $folderUrl): void
    {
        $this->client->patch(config('vigil.airtable.tables.acts'), $recordId, [
            '💼 საქაღალდე' => $folderUrl,
        ]);
    }

    /**
     * Find a single ACT by its act number.
     */
    public function getActByNumber(string $actNumber): ?array
    {
        $records = $this->client->get(config('vigil.airtable.tables.acts'), [
            'filterByFormula' => "{🔖 აქტის ნომერი} = '{$actNumber}'",
            'maxRecords' => 1,
        ]);

        return $records[0] ?? null;
    }

    /**
     * A single ACT record by its Airtable record ID.
     */
    public function getAct(string $recordId): array
    {
        return $this->client->getRecord(config('vigil.airtable.tables.acts'), $recordId);
    }

    /**
     * Map of ACT number => whether 📥 ხელმოწერები is ticked.
     *
     * @return array<string, bool>
     */
    public function getSignatureStatuses(): array
    {
        $statuses = [];

        foreach ($this->getAllActs() as $act) {
            $number = (string) ($act['fields']['🔖 აქტის ნომერი'] ?? '');

            if ($number !== '') {
                $statuses[$number] = ! empty($act['fields']['📥 ხელმოწერები']);
            }
        }

        return $statuses;
    }

    /**
     * Tick a single checkbox field on an ACT record.
     */
    public function tickActCheckbox(string $recordId, string $field): void
    {
        $this->client->patch(config('vigil.airtable.tables.acts'), $recordId, [
            $field => true,
        ]);
    }

    /**
     * Untick a single checkbox field on an ACT record.
     */
    public function untickActCheckbox(string $recordId, string $field): void
    {
        $this->client->patch(config('vigil.airtable.tables.acts'), $recordId, [
            $field => false,
        ]);
    }

    /**
     * Get all manager contacts for correspondence alerts.
     */
    public function getManagers(): array
    {
        $teams = $this->getTeams();

        return array_filter(
            $teams,
            fn ($team) => ($team['fields']['🏷️ როლი'] ?? '') === 'მენეჯერი'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DEADLINES (ვადები)
    |--------------------------------------------------------------------------
    */

    /**
     * Get all pending deadlines that are approaching or overdue.
     */
    public function getUpcomingDeadlines(): array
    {
        return $this->client->get(config('vigil.airtable.tables.deadlines'), [
            'filterByFormula' => "{🚦 სტატუსი} = '⏳ მომლოდინე'",
        ]);
    }

    /**
     * Mark a deadline as overdue.
     */
    public function markDeadlineOverdue(string $recordId): void
    {
        $this->client->patch(config('vigil.airtable.tables.deadlines'), $recordId, [
            '🚦 სტატუსი' => '🔴 გადაცილებული',
        ]);
    }

    /**
     * Get correspondence with approaching promised dates.
     */
    public function getCorrespondenceWithPromisedDates(): array
    {
        return $this->client->get(config('vigil.airtable.tables.correspondence'), [
            'filterByFormula' => "AND({📅 დაპირებული თარიღი} != '', {🚦სტატუსი} != '📤 პასუხი გაგზავნილი')",
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BACKUP
    |--------------------------------------------------------------------------
    */

    public function getAllForBackup(): array
    {
        return [
            'acts' => $this->client->get(config('vigil.airtable.tables.acts')),
            'deadlines' => $this->client->get(config('vigil.airtable.tables.deadlines')),
            'sectors' => $this->client->get(config('vigil.airtable.tables.sectors')),
            'correspondence' => $this->client->get(config('vigil.airtable.tables.correspondence')),
            'teams' => $this->client->get(config('vigil.airtable.tables.teams')),
            'businesses' => $this->client->get(config('vigil.airtable.tables.businesses')),
        ];
    }

    /**
     * Get all sectors.
     */
    public function getSectors(): array
    {
        return $this->client->get(config('vigil.airtable.tables.sectors'));
    }

    /**
     * Mark sector as celebrated.
     */
    public function markSectorCelebrated(string $recordId): void
    {
        $this->client->patch(config('vigil.airtable.tables.sectors'), $recordId, [
            '🎉 აღინიშნა' => true,
        ]);
    }

    /**
     * Get a single record from the updates table.
     */
    public function getUpdateRecord(string $recordId): array
    {
        return $this->client->getRecord(
            config('vigil.airtable.tables.updates'),
            $recordId
        );
    }

    /**
     * Get a single team record by ID from the გუნდები table.
     */
    public function getTeamRecord(string $recordId): array
    {
        return $this->client->getRecord(
            config('vigil.airtable.tables.teams'),
            $recordId
        );
    }
}
