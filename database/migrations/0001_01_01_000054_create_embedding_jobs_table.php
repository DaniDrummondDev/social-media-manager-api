<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embedding_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('entity_type', 50);
            $table->uuid('entity_id');
            $table->string('status', 20)->default('pending');
            $table->string('model_used', 50)->nullable();
            $table->integer('tokens_used')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('completed_at')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['entity_type', 'entity_id']);
            $table->index(['organization_id', 'entity_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embedding_jobs');
    }
};
