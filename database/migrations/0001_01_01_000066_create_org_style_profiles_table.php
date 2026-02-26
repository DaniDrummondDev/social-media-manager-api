<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_style_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('generation_type', 50);
            $table->integer('sample_size')->default(0);
            $table->json('tone_preferences')->default('{}');
            $table->json('length_preferences')->default('{}');
            $table->json('vocabulary_preferences')->default('{}');
            $table->json('structure_preferences')->default('{}');
            $table->json('hashtag_preferences')->default('{}');
            $table->text('style_summary')->nullable();
            $table->string('confidence_level', 10)->default('low');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->unique(['organization_id', 'generation_type']);
            $table->index(['organization_id', 'generation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_style_profiles');
    }
};
