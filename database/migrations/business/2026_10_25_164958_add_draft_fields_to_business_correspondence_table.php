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
        Schema::connection('business')->table('business_correspondence', function (Blueprint $table) {
            $table->string('recipient_email')->nullable()->after('business_id');
            $table->json('cc_emails')->nullable()->after('recipient_email');
            $table->string('subject')->nullable()->after('cc_emails');
            $table->text('body')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::connection('business')->table('business_correspondence', function (Blueprint $table) {
            $table->dropColumn(['recipient_email', 'cc_emails', 'subject', 'body']);
        });
    }
};
