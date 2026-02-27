<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_performance_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('ad_insight_type', 50);
            $table->json('insight_data')->default('{}');
            $table->integer('sample_size')->default(0);
            $table->string('confidence_level', 10)->default('low');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->unique(['organization_id', 'ad_insight_type']);
            $table->index(['organization_id', 'ad_insight_type']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_performance_insights');
    }
};
