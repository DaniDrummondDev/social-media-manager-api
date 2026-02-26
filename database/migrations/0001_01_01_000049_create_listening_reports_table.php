<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->json('query_ids');
            $table->date('period_from');
            $table->date('period_to');
            $table->integer('total_mentions')->default(0);
            $table->json('sentiment_breakdown')->nullable();
            $table->json('top_authors')->nullable();
            $table->json('top_keywords')->nullable();
            $table->json('platform_breakdown')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('file_path', 500)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_reports');
    }
};
