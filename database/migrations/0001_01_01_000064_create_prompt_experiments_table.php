<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('generation_type', 50);
            $table->string('name', 200);
            $table->string('status', 20)->default('draft');
            $table->uuid('variant_a_id');
            $table->uuid('variant_b_id');
            $table->decimal('traffic_split', 3, 2)->default(0.50);
            $table->integer('min_sample_size')->default(50);
            $table->integer('variant_a_uses')->default(0);
            $table->integer('variant_a_accepted')->default(0);
            $table->integer('variant_b_uses')->default(0);
            $table->integer('variant_b_accepted')->default(0);
            $table->uuid('winner_id')->nullable();
            $table->decimal('confidence_level', 5, 4)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('variant_a_id')
                ->references('id')->on('prompt_templates');

            $table->foreign('variant_b_id')
                ->references('id')->on('prompt_templates');

            $table->foreign('winner_id')
                ->references('id')->on('prompt_templates');

            $table->index(['organization_id', 'generation_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_experiments');
    }
};
