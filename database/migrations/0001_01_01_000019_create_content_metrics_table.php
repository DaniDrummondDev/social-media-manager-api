<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('content_id');
            $table->uuid('social_account_id');
            $table->string('provider', 20);
            $table->string('external_post_id', 255)->nullable();
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->bigInteger('saves')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->bigInteger('views')->nullable();
            $table->bigInteger('watch_time_seconds')->nullable();
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->cascadeOnDelete();

            $table->unique(['content_id', 'social_account_id']);
            $table->index('provider');
            $table->index('synced_at');
            $table->index('engagement_rate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_metrics');
    }
};
