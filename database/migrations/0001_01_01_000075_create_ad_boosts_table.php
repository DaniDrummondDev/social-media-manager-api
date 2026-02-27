<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_boosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('scheduled_post_id');
            $table->uuid('ad_account_id');
            $table->uuid('audience_id');
            $table->integer('budget_amount_cents');
            $table->string('budget_currency', 3);
            $table->string('budget_type', 20);
            $table->integer('duration_days');
            $table->string('objective', 30);
            $table->string('status', 20)->default('draft');
            $table->json('external_ids')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
            $table->index('ad_account_id');

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('ad_account_id')->references('id')->on('ad_accounts')->cascadeOnDelete();
            $table->foreign('audience_id')->references('id')->on('audiences')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_boosts');
    }
};
