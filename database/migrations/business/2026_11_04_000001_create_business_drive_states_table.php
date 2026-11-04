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
        Schema::connection('business')->create('business_drive_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('subfolder_name');
            $table->boolean('has_files')->default(false);
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['business_id', 'subfolder_name']);
        });
    }

    public function down(): void
    {
        Schema::connection('business')->dropIfExists('business_drive_states');
    }
};
