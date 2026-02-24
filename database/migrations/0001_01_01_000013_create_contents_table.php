<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('campaign_id');
            $table->uuid('created_by');
            $table->string('title', 500)->nullable();
            $table->text('body')->nullable();
            $table->json('hashtags')->default('[]');
            $table->string('status', 20)->default('draft');
            $table->uuid('ai_generation_id')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('purge_at')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['campaign_id', 'status', 'created_at']);
            $table->index(['organization_id', 'created_at']);
            $table->index('status');
            $table->index('purge_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
