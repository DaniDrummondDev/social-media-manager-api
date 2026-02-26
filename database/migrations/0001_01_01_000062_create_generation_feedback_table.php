<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->uuid('ai_generation_id');
            $table->string('action', 20);
            $table->json('original_output');
            $table->json('edited_output')->nullable();
            $table->json('diff_summary')->nullable();
            $table->uuid('content_id')->nullable();
            $table->string('generation_type', 50);
            $table->integer('time_to_decision_ms')->nullable();
            $table->timestamp('created_at');

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->index(['organization_id', 'generation_type', 'action', 'created_at']);
            $table->index('ai_generation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_feedback');
    }
};
