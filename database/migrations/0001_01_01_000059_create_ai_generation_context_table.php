<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generation_context', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('context_type', 50);
            $table->json('context_data');
            $table->integer('max_tokens')->default(500);
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'context_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_context');
    }
};
