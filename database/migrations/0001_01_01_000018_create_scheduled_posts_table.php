<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('content_id');
            $table->uuid('social_account_id');
            $table->uuid('scheduled_by');
            $table->timestamp('scheduled_at');
            $table->string('status', 20)->default('pending');
            $table->timestamp('published_at')->nullable();
            $table->string('external_post_id', 255)->nullable();
            $table->text('external_post_url')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->timestamp('last_attempted_at')->nullable();
            $table->json('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->string('idempotency_key', 255)->nullable()->unique();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->cascadeOnDelete();
            $table->foreign('scheduled_by')->references('id')->on('users');

            $table->index(['organization_id', 'status', 'scheduled_at']);
            $table->index(['organization_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index('content_id');
            $table->index('social_account_id');
            $table->index('next_retry_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
