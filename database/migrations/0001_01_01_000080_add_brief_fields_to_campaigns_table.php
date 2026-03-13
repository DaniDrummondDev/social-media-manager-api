<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('brief_text')->nullable();
            $table->string('brief_target_audience', 500)->nullable();
            $table->text('brief_restrictions')->nullable();
            $table->string('brief_cta', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['brief_text', 'brief_target_audience', 'brief_restrictions', 'brief_cta']);
        });
    }
};
