<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('social_account_id')->nullable();
            $table->string('insight_type', 50);
            $table->json('insight_data');
            $table->integer('source_comment_count')->default(0);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->nullOnDelete();
            $table->index(['organization_id', 'insight_type', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_insights');
    }
};
