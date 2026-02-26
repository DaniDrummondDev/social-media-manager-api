<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_validations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('prediction_id');
            $table->uuid('content_id');
            $table->string('provider', 30);
            $table->smallInteger('predicted_score');
            $table->decimal('actual_engagement_rate', 8, 4)->nullable();
            $table->smallInteger('actual_normalized_score')->nullable();
            $table->smallInteger('absolute_error')->nullable();
            $table->decimal('prediction_accuracy', 5, 2)->nullable();
            $table->json('metrics_snapshot')->default('{}');
            $table->timestamp('validated_at');
            $table->timestamp('metrics_captured_at');
            $table->timestamp('created_at');

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('prediction_id')
                ->references('id')->on('performance_predictions')
                ->cascadeOnDelete();

            $table->unique('prediction_id');
            $table->index(['organization_id', 'validated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_validations');
    }
};
