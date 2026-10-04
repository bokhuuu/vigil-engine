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
        Schema::connection('business')->create('business_correspondence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->unsignedTinyInteger('round');
            $table->date('sent_at')->nullable();
            $table->date('deadline_at')->nullable();
            $table->date('received_at')->nullable();
            $table->string('drive_folder_url')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'received', 'overdue'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('business')->dropIfExists('business_correspondence');
    }
};
