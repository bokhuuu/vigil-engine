<?php

declare(strict_types=1);

use App\Support\ActRangeHelper;

describe('ActRangeHelper', function () {

    // ── primaryNumber ────────────────────────────────────────────────────

    describe('primaryNumber', function () {
        it('returns integer for a plain number', function () {
            expect(ActRangeHelper::primaryNumber('169'))->toBe(169);
        });

        it('returns first segment for hyphenated two-part number', function () {
            expect(ActRangeHelper::primaryNumber('3-1'))->toBe(3);
        });

        it('returns first segment for hyphenated three-part number', function () {
            expect(ActRangeHelper::primaryNumber('3-1-1'))->toBe(3);
        });

        it('handles large plain number', function () {
            expect(ActRangeHelper::primaryNumber('730'))->toBe(730);
        });

        it('returns 0 for empty string', function () {
            expect(ActRangeHelper::primaryNumber(''))->toBe(0);
        });

        it('handles leading/trailing whitespace', function () {
            expect(ActRangeHelper::primaryNumber(' 42 '))->toBe(42);
        });
    });

    // ── slugForRange ─────────────────────────────────────────────────────

    describe('slugForRange', function () {
        it('returns all for null range', function () {
            expect(ActRangeHelper::slugForRange(null))->toBe('all');
        });

        it('returns from-to string for a range', function () {
            expect(ActRangeHelper::slugForRange(['from' => 1, 'to' => 100]))->toBe('1-100');
        });

        it('returns correct slug for mid range', function () {
            expect(ActRangeHelper::slugForRange(['from' => 201, 'to' => 300]))->toBe('201-300');
        });

        it('handles single-ACT range', function () {
            expect(ActRangeHelper::slugForRange(['from' => 42, 'to' => 42]))->toBe('42-42');
        });
    });

    // ── labelForRange ────────────────────────────────────────────────────

    describe('labelForRange', function () {
        it('returns Georgian all label for null', function () {
            expect(ActRangeHelper::labelForRange(null))->toBe('ყველა');
        });

        it('returns formatted label for range', function () {
            expect(ActRangeHelper::labelForRange(['from' => 1, 'to' => 100]))->toBe('1 – 100');
        });
    });

    // ── isValid ──────────────────────────────────────────────────────────

    describe('isValid', function () {
        it('accepts valid range', function () {
            expect(ActRangeHelper::isValid(['from' => 1, 'to' => 100]))->toBeTrue();
        });

        it('accepts single-entry range where from equals to', function () {
            expect(ActRangeHelper::isValid(['from' => 50, 'to' => 50]))->toBeTrue();
        });

        it('rejects range where from is zero', function () {
            expect(ActRangeHelper::isValid(['from' => 0, 'to' => 100]))->toBeFalse();
        });

        it('rejects range where to is less than from', function () {
            expect(ActRangeHelper::isValid(['from' => 100, 'to' => 50]))->toBeFalse();
        });

        it('rejects negative from', function () {
            expect(ActRangeHelper::isValid(['from' => -1, 'to' => 100]))->toBeFalse();
        });
    });

    // ── filterFolderIndex ────────────────────────────────────────────────

    describe('filterFolderIndex', function () {
        $folderIndex = [
            'drive-id-A' => ['act_number' => '3',     'act_record_id' => 'recA'],
            'drive-id-B' => ['act_number' => '3-1',   'act_record_id' => 'recB'],
            'drive-id-C' => ['act_number' => '3-1-1', 'act_record_id' => 'recC'],
            'drive-id-D' => ['act_number' => '99',    'act_record_id' => 'recD'],
            'drive-id-E' => ['act_number' => '100',   'act_record_id' => 'recE'],
            'drive-id-F' => ['act_number' => '101',   'act_record_id' => 'recF'],
            'drive-id-G' => ['act_number' => '169',   'act_record_id' => 'recG'],
            'drive-id-H' => ['act_number' => '730',   'act_record_id' => 'recH'],
        ];

        it('returns all entries when range is null', function () use ($folderIndex) {
            $result = ActRangeHelper::filterFolderIndex($folderIndex, null);
            expect($result)->toHaveCount(8);
        });

        it('includes plain and hyphenated ACTs in range 1-100', function () use ($folderIndex) {
            $result = ActRangeHelper::filterFolderIndex($folderIndex, ['from' => 1, 'to' => 100]);
            expect($result)->toHaveCount(5); // 3, 3-1, 3-1-1, 99, 100
            expect(array_keys($result))->toContain('drive-id-A');
            expect(array_keys($result))->toContain('drive-id-B');
            expect(array_keys($result))->toContain('drive-id-C');
            expect(array_keys($result))->toContain('drive-id-D');
            expect(array_keys($result))->toContain('drive-id-E');
        });

        it('excludes ACTs below range', function () use ($folderIndex) {
            $result = ActRangeHelper::filterFolderIndex($folderIndex, ['from' => 100, 'to' => 200]);
            expect(array_keys($result))->not->toContain('drive-id-A'); // ACT 3
            expect(array_keys($result))->toContain('drive-id-E');  // ACT 100
            expect(array_keys($result))->toContain('drive-id-F');  // ACT 101
            expect(array_keys($result))->toContain('drive-id-G');  // ACT 169
        });

        it('excludes ACTs above range', function () use ($folderIndex) {
            $result = ActRangeHelper::filterFolderIndex($folderIndex, ['from' => 1, 'to' => 100]);
            expect(array_keys($result))->not->toContain('drive-id-F'); // ACT 101
            expect(array_keys($result))->not->toContain('drive-id-H'); // ACT 730
        });

        it('handles single-ACT range', function () use ($folderIndex) {
            $result = ActRangeHelper::filterFolderIndex($folderIndex, ['from' => 169, 'to' => 169]);
            expect($result)->toHaveCount(1);
            expect(array_keys($result))->toContain('drive-id-G');
        });

        it('returns empty array when no ACTs match range', function () use ($folderIndex) {
            $result = ActRangeHelper::filterFolderIndex($folderIndex, ['from' => 500, 'to' => 600]);
            expect($result)->toBeEmpty();
        });

        it('preserves original drive folder ID keys', function () use ($folderIndex) {
            $result = ActRangeHelper::filterFolderIndex($folderIndex, ['from' => 1, 'to' => 100]);
            foreach (array_keys($result) as $key) {
                expect($key)->toStartWith('drive-id-');
            }
        });
    });
});
