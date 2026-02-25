<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name', 255);
            $table->smallInteger('priority');
            $table->string('action_type', 30);
            $table->text('response_template')->nullable();
            $table->uuid('webhook_id')->nullable();
            $table->integer('delay_seconds')->default(120);
            $table->integer('daily_limit')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('applies_to_networks')->nullable();
            $table->json('applies_to_campaigns')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('purge_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'is_active']);
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX idx_automation_rules_org_priority ON automation_rules (organization_id, priority) WHERE deleted_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
