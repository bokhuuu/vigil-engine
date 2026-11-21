<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('business')->table('business_emails', function (Blueprint $table) {
            $table->string('gmail_message_id')->nullable()->after('business_id');
            $table->unique('gmail_message_id');
        });
    }

    public function down(): void
    {
        Schema::connection('business')->table('business_emails', function (Blueprint $table) {
            $table->dropUnique(['gmail_message_id']);
            $table->dropColumn('gmail_message_id');
        });
    }
};
