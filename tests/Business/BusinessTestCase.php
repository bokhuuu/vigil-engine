<?php

declare(strict_types=1);

namespace Tests\Business;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class BusinessTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'business',
            '--path' => 'database/migrations/business',
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', [
            '--database' => 'business',
            '--path' => 'database/migrations/business',
            '--force' => true,
        ]);

        parent::tearDown();
    }
}
