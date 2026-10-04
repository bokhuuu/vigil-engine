<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'business';

    public function up(): void
    {
        Schema::connection('business')->table('businesses', function (Blueprint $table) {
            $table->string('act_number')->nullable()->after('registration_code');
        });
    }

    public function down(): void
    {
        Schema::connection('business')->table('businesses', function (Blueprint $table) {
            $table->dropColumn('act_number');
        });
    }
};
