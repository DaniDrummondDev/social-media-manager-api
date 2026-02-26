<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_gap_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->json('competitor_query_ids');
            $table->timestamp('analysis_period_start');
            $table->timestamp('analysis_period_end');
            $table->json('our_topics')->default('[]');
            $table->json('competitor_topics')->default('[]');
            $table->json('gaps')->default('[]');
            $table->json('opportunities')->default('[]');
            $table->string('status', 50)->default('generating');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_gap_analyses');
    }
};
