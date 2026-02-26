<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('social_account_id')->nullable();
            $table->string('provider', 30)->nullable();
            $table->string('status', 20)->default('generating');
            $table->integer('total_contents_analyzed')->default(0);
            $table->json('top_themes')->default('[]');
            $table->json('engagement_patterns')->default('{}');
            $table->json('content_fingerprint')->default('{}');
            $table->json('high_performer_traits')->default('{}');
            $table->text('centroid_embedding')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'social_account_id', 'provider']);
            $table->index(['organization_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_profiles');
    }
};
