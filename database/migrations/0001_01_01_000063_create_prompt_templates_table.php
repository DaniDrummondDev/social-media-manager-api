<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->string('generation_type', 50);
            $table->string('version', 20);
            $table->string('name', 200);
            $table->text('system_prompt');
            $table->text('user_prompt_template');
            $table->json('variables')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->integer('total_uses')->default(0);
            $table->integer('total_accepted')->default(0);
            $table->integer('total_edited')->default(0);
            $table->integer('total_rejected')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->unique(['organization_id', 'generation_type', 'version']);
            $table->index(['organization_id', 'generation_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
    }
};
