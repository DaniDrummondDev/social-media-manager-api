<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('plan_id');
            $table->string('status', 20)->default('active');
            $table->string('billing_cycle', 10)->default('monthly');
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->text('cancel_reason')->nullable();
            $table->string('cancel_feedback', 50)->nullable();
            $table->string('external_subscription_id', 255)->nullable();
            $table->string('external_customer_id', 255)->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
            $table->index(['status', 'current_period_end']);
        });

        if ($driver === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX uq_subscriptions_org_active ON subscriptions (organization_id) WHERE status IN ('trialing', 'active', 'past_due')");
            DB::statement('CREATE UNIQUE INDEX uq_subscriptions_external ON subscriptions (external_subscription_id) WHERE external_subscription_id IS NOT NULL');
            DB::statement("CREATE INDEX idx_subscriptions_trial ON subscriptions (trial_ends_at) WHERE status = 'trialing' AND trial_ends_at IS NOT NULL");
            DB::statement("CREATE INDEX idx_subscriptions_past_due ON subscriptions (updated_at) WHERE status = 'past_due'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
