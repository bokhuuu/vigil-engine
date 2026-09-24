<?php

declare(strict_types=1);

namespace App\Services;

class ActCompletionService
{
    public function __construct(
        private AirtableService $airtable,
        private GoogleDriveService $drive,
        private ManagerNotifier $notifier,
        private TelegramService $telegram,
    ) {}

    /**
     * Verify an ACT marked complete against Airtable + Drive and notify.
     * Returns the list of missing items (empty = verified complete),
     * or null when the ACT is not marked complete at all.
     *
     * @return array<int, string>|null
     */
    public function check(string $recordId): ?array
    {
        $act = $this->airtable->getAct($recordId);
        $fields = $act['fields'] ?? [];

        if (($fields['🚦სტატუსი'] ?? '') !== config('vigil.airtable.values.status_done')) {
            return null;
        }

        $number = e((string) ($fields['🔖 აქტის ნომერი'] ?? $recordId));
        $link = e($this->airtable->recordUrl(config('vigil.airtable.tables.acts'), $recordId));
        $missing = $this->missingItems($fields);

        if ($missing === []) {
            $this->notifier->notify(
                "✅ <b>ACT {$number}</b> დასრულდა\n"
                    ."ყველა დოკუმენტი ადგილზეა.\n"
                    ."🔗 <a href=\"{$link}\">Airtable</a>"
            );
        } else {
            $this->telegram->sendToMe(
                "⚠️ <b>ACT {$number}</b> მონიშნულია დასრულებულად, მაგრამ აკლია:\n"
                    .'• '.implode("\n• ", array_map('e', $missing))."\n"
                    ."🔗 <a href=\"{$link}\">Airtable</a>"
            );
        }

        return $missing;
    }

    /**
     * @return array<int, string>
     */
    private function missingItems(array $fields): array
    {
        $missing = [];

        if (empty($fields['📥 ხელმოწერები'])) {
            $missing[] = 'ხელმოწერები';
        }

        $folderUrl = $fields['💼 საქაღალდე'] ?? null;

        if (! $folderUrl) {
            $missing[] = 'Drive საქაღალდე';

            return $missing;
        }

        try {
            return array_merge($missing, $this->drive->emptyRequiredSubfolders($folderUrl, $fields));
        } catch (\Throwable $e) {
            report($e); // Sentry + log

            return array_merge($missing, ['Drive ვერ შემოწმდა']);
        }
    }
}
