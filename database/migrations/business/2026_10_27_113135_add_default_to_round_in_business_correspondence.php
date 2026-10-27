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
            $table->unsignedTinyInteger('round')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::connection('business')->table('business_correspondence', function (Blueprint $table) {
            $table->unsignedTinyInteger('round')->default(null)->change();
        });
    }
};
