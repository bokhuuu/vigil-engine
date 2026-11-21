<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('business')->create('business_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('registration_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('business')->dropIfExists('business_aliases');
    }
};
