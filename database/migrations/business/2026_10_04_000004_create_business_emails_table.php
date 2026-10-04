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
        Schema::connection('business')->create('business_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('email_from');
            $table->string('email_subject');
            $table->enum('matched_by', ['registration_code', 'name', 'unmatched'])->default('unmatched');
            $table->string('drive_folder_url')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('business')->dropIfExists('business_emails');
    }
};
