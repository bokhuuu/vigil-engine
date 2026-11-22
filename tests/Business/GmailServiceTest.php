<?php

declare(strict_types=1);

use App\Services\GmailService;
use Google\Client;

test('extracts drive file ids from body', function () {
    $gmail = new GmailService(new Client);

    $body = 'balansi_2023.pdf<https://drive.google.com/file/d/1NO1l_fU0sB6xFbUcKhiYay_PMBBAX1z4/view?usp=drive_web>';

    $ids = $gmail->getDriveLinksFromBody($body);

    expect($ids)->toBe(['1NO1l_fU0sB6xFbUcKhiYay_PMBBAX1z4']);
});

test('extracts multiple unique drive file ids', function () {
    $gmail = new GmailService(new Client);

    $body = 'file1<https://drive.google.com/file/d/AAA111bbb222/view> file2<https://drive.google.com/file/d/CCC333ddd444/view> duplicate<https://drive.google.com/file/d/AAA111bbb222/view>';

    $ids = $gmail->getDriveLinksFromBody($body);

    expect($ids)->toHaveCount(2)
        ->and($ids)->toContain('AAA111bbb222')
        ->and($ids)->toContain('CCC333ddd444');
});

test('returns empty array when no drive links in body', function () {
    $gmail = new GmailService(new Client);

    $ids = $gmail->getDriveLinksFromBody('plain text with no links');

    expect($ids)->toBe([]);
});
