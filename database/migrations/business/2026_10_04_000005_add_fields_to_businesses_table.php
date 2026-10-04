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
            $table->enum('type', ['მეპატრონე', 'მოიჯარე', 'მეიჯარე', 'სხვა'])->default('მეპატრონე')->after('status');
            $table->string('owner_name')->nullable()->after('type');
            $table->string('owner_phone')->nullable()->after('owner_name');
            $table->string('owner_email')->nullable()->after('owner_phone');
            $table->string('accountant_name')->nullable()->after('owner_email');
            $table->string('accountant_phone')->nullable()->after('accountant_name');
            $table->string('accountant_email')->nullable()->after('accountant_phone');
            $table->text('notes')->nullable()->after('accountant_email');
        });
    }

    public function down(): void
    {
        Schema::connection('business')->table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'owner_name',
                'owner_phone',
                'owner_email',
                'accountant_name',
                'accountant_phone',
                'accountant_email',
                'notes',
            ]);
        });
    }
};
