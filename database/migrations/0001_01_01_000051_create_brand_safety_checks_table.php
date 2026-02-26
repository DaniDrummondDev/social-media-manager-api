<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_safety_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('content_id');
            $table->string('provider', 30)->nullable();
            $table->string('overall_status', 20)->default('pending');
            $table->smallInteger('overall_score')->nullable();
            $table->json('checks')->default('[]');
            $table->string('model_used', 50)->nullable();
            $table->integer('tokens_input')->nullable();
            $table->integer('tokens_output')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('created_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['content_id', 'provider']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_safety_checks');
    }
};
