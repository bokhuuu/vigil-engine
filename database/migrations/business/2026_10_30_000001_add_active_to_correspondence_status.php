<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'business';

    public function up(): void
    {
        DB::connection('business')->statement(
            'ALTER TABLE business_correspondence DROP CONSTRAINT business_correspondence_status_check'
        );

        DB::connection('business')->statement(
            "ALTER TABLE business_correspondence ADD CONSTRAINT business_correspondence_status_check
             CHECK (status IN ('pending', 'active', 'received', 'overdue'))"
        );
    }

    public function down(): void
    {
        DB::connection('business')->statement(
            'ALTER TABLE business_correspondence DROP CONSTRAINT business_correspondence_status_check'
        );

        DB::connection('business')->statement(
            "ALTER TABLE business_correspondence ADD CONSTRAINT business_correspondence_status_check
             CHECK (status IN ('pending', 'received', 'overdue'))"
        );
    }
};
