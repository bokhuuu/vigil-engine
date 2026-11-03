<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'business';
    }

    public function up(): void
    {
        DB::connection('business')->statement(
            'ALTER TABLE businesses DROP CONSTRAINT businesses_status_check'
        );

        DB::connection('business')->statement(
            "ALTER TABLE businesses ADD CONSTRAINT businesses_status_check
             CHECK (status IN ('pending', 'active', 'complete', 'მიმდინარე'))"
        );
    }

    public function down(): void
    {
        DB::connection('business')->statement(
            "UPDATE businesses SET status = 'active' WHERE status = 'მიმდინარე'"
        );

        DB::connection('business')->statement(
            'ALTER TABLE businesses DROP CONSTRAINT businesses_status_check'
        );

        DB::connection('business')->statement(
            "ALTER TABLE businesses ADD CONSTRAINT businesses_status_check
         CHECK (status IN ('pending', 'active', 'complete'))"
        );
    }
};
