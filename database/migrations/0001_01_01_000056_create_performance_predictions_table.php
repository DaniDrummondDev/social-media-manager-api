<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_predictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('content_id');
            $table->string('provider', 30);
            $table->smallInteger('overall_score');
            $table->json('breakdown');
            $table->json('similar_content_ids')->nullable();
            $table->json('recommendations')->default('[]');
            $table->string('model_version', 20)->default('v1');
            $table->timestamp('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();
            $table->index(['content_id', 'provider']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_predictions');
    }
};
