<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_time_recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('social_account_id')->nullable();
            $table->string('provider', 30)->nullable();
            $table->smallInteger('day_of_week')->nullable();
            $table->json('heatmap');
            $table->json('top_slots');
            $table->json('worst_slots')->default('[]');
            $table->integer('sample_size');
            $table->string('confidence_level', 10)->default('low');
            $table->timestamp('calculated_at');
            $table->timestamp('expires_at');
            $table->timestamp('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_time_recommendations');
    }
};
