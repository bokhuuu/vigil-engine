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
        Schema::connection('business')->create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_code')->unique();
            $table->unsignedTinyInteger('lot_number');
            $table->enum('status', ['pending', 'active', 'complete'])->default('pending');
            $table->string('drive_folder_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('business')->dropIfExists('businesses');
    }
};
