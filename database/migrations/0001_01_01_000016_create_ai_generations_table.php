<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->string('type', 30);
            $table->json('input');
            $table->json('output');
            $table->string('model_used', 50);
            $table->integer('tokens_input')->default(0);
            $table->integer('tokens_output')->default(0);
            $table->decimal('cost_estimate', 10, 6)->default(0);
            $table->integer('duration_ms')->default(0);
            $table->uuid('prompt_template_id')->nullable();
            $table->uuid('experiment_id')->nullable();
            $table->json('rag_context_used')->nullable();
            $table->boolean('style_context_used')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
