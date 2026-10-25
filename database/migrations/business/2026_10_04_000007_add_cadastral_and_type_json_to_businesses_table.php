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
            $table->dropColumn('type');
        });

        Schema::connection('business')->table('businesses', function (Blueprint $table) {
            $table->json('type')->default('[]')->after('status');
            $table->string('cadastral_code')->nullable()->after('act_number');
        });
    }

    public function down(): void
    {
        Schema::connection('business')->table('businesses', function (Blueprint $table) {
            $table->dropColumn(['type', 'cadastral_code']);
        });

        Schema::connection('business')->table('businesses', function (Blueprint $table) {
            $table->enum('type', ['მეპატრონე', 'მოიჯარე', 'მეიჯარე', 'სხვა'])->default('მეპატრონე')->after('status');
        });
    }
};
