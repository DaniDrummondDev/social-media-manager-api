<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->uuid('organization_id')->primary();
            $table->string('default_tone', 30)->default('professional');
            $table->text('custom_tone_description')->nullable();
            $table->string('default_language', 10)->default('pt_BR');
            $table->integer('monthly_generation_limit')->default(500);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
