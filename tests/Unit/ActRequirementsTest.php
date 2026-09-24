<?php

declare(strict_types=1);

use App\Services\ActRequirements;
use Tests\TestCase;

uses(TestCase::class);

test('ACT with no flags requires only photos and signatures', function () {
    expect((new ActRequirements)->requiredDocFields([]))
        ->toBe(['📥 ფოტოები', '📥 ხელმოწერები']);
});

test('building requires drawings and cost estimate', function () {
    $required = (new ActRequirements)->requiredDocFields([
        '🏠 შენობა/ნაგებობები' => true,
    ]);

    expect($required)->toContain('📥 შიდა ნახაზები', '📥 ხარჯთაღრიცხვა')
        ->not->toContain('📥 გამიჯვნები', '📥 ტოპო ნარგავები');
});

test('fence alone requires cost estimate but not drawings', function () {
    $required = (new ActRequirements)->requiredDocFields([
        '🏠 ღობე - ჭიშკარი' => true,
    ]);

    expect($required)->toContain('📥 ხარჯთაღრიცხვა')
        ->not->toContain('📥 შიდა ნახაზები');
});

test('boundary flag requires გამიჯვნები', function () {
    expect((new ActRequirements)->requiredDocFields(['📋 გამიჯვნა საჭიროა' => true]))
        ->toContain('📥 გამიჯვნები');
});

test('either plant flag requires topo', function () {
    $service = new ActRequirements;

    expect($service->requiredDocFields(['🏠 მრავალწლოვანი' => true]))->toContain('📥 ტოპო ნარგავები');
    expect($service->requiredDocFields(['🏠 ერთწლოვანი' => true]))->toContain('📥 ტოპო ნარგავები');
});

test('multiple cost flags do not duplicate the cost estimate', function () {
    $required = (new ActRequirements)->requiredDocFields([
        '🏠 შენობა/ნაგებობები' => true,
        '🏠 ღობე - ჭიშკარი' => true,
        '🏠 სამ-ფაზიანი დენი' => true,
    ]);

    expect(array_count_values($required)['📥 ხარჯთაღრიცხვა'])->toBe(1);
});

test('ACT with no flags needs only სკანი and ფოტოები folders', function () {
    expect((new ActRequirements)->requiredSubfolders([]))
        ->toBe(['სკანი', 'ფოტოები']);
});

test('building needs drawings and cost folders', function () {
    expect((new ActRequirements)->requiredSubfolders(['🏠 შენობა/ნაგებობები' => true]))
        ->toBe(['სკანი', 'ფოტოები', 'შიდა ნახაზები', 'ხარჯთაღრიცხვა']);
});

test('fence alone needs cost folder but not drawings', function () {
    expect((new ActRequirements)->requiredSubfolders(['🏠 ღობე - ჭიშკარი' => true]))
        ->toBe(['სკანი', 'ფოტოები', 'ხარჯთაღრიცხვა']);
});

test('boundary and plants add their folders', function () {
    expect((new ActRequirements)->requiredSubfolders([
        '📋 გამიჯვნა საჭიროა' => true,
        '🏠 ერთწლოვანი' => true,
    ]))->toBe(['სკანი', 'ფოტოები', 'გამიჯვნები', 'ტოპო ნარგავები']);
});
