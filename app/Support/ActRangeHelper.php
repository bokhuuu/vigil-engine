<?php

declare(strict_types=1);

namespace App\Support;

class ActRangeHelper
{
    /**
     * Extract the primary (first) numeric segment from an ACT number.
     *
     * Examples:
     *   '169'     → 169
     *   '3-1'     → 3
     *   '3-1-1'   → 3
     *   '730'     → 730
     */
    public static function primaryNumber(string $actNumber): int
    {
        $parts = explode('-', trim($actNumber));

        return (int) ($parts[0] ?? 0);
    }

    /**
     * Return a stable cache-key slug for a range.
     *
     *   null                       → 'all'
     *   ['from' => 1, 'to' => 100] → '1-100'
     */
    public static function slugForRange(?array $range): string
    {
        if ($range === null) {
            return 'all';
        }

        $from = (int) ($range['from'] ?? 0);
        $to = (int) ($range['to'] ?? 0);

        return "{$from}-{$to}";
    }

    /**
     * Human-readable label for display in the UI.
     *
     *   null                       → 'ყველა'
     *   ['from' => 1, 'to' => 100] → '1 – 100'
     */
    public static function labelForRange(?array $range): string
    {
        if ($range === null) {
            return 'ყველა';
        }

        $from = (int) ($range['from'] ?? 0);
        $to = (int) ($range['to'] ?? 0);

        return "{$from} – {$to}";
    }

    /**
     * Filter a folderIndex array to only entries whose ACT primary number
     * falls within the given range (inclusive on both ends).
     *
     * folderIndex structure (from DriveWatcherStateRepository::getFolderIndex()):
     *   [
     *     'driveFolderId' => ['act_number' => '3-1', 'act_record_id' => 'recXXX', ...],
     *     ...
     *   ]
     *
     * When $range is null, the full index is returned unchanged.
     *
     * @param  array<string, array{act_number: string, act_record_id?: string}>  $folderIndex
     * @param  array{from: int, to: int}|null  $range
     * @return array<string, array{act_number: string, act_record_id?: string}>
     */
    public static function filterFolderIndex(array $folderIndex, ?array $range): array
    {
        if ($range === null) {
            return $folderIndex;
        }

        $from = (int) ($range['from'] ?? 0);
        $to = (int) ($range['to'] ?? 0);

        return array_filter(
            $folderIndex,
            function (array $info) use ($from, $to): bool {
                $actNumber = (string) ($info['act_number'] ?? '');
                $primary = self::primaryNumber($actNumber);

                return $primary >= $from && $primary <= $to;
            }
        );
    }

    /**
     * Validate a range array.
     * Returns true if from >= 1, to >= from.
     *
     * @param  array{from: int, to: int}  $range
     */
    public static function isValid(array $range): bool
    {
        $from = (int) ($range['from'] ?? 0);
        $to = (int) ($range['to'] ?? 0);

        return $from >= 1 && $to >= $from;
    }
}
