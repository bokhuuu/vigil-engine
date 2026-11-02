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
        Schema::connection('business')->create('act_excel_entries', function (Blueprint $table) {
            $table->id();
            $table->string('act_record_id')->unique();
            $table->timestamp('entered_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('business')->dropIfExists('act_excel_entries');
    }
};
