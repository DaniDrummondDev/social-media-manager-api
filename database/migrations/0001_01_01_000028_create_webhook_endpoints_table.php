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

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name', 255);
            $table->string('url', 2000);
            $table->text('secret');
            $table->jsonb('events');
            $table->jsonb('headers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_delivery_at')->nullable();
            $table->smallInteger('last_delivery_status')->nullable();
            $table->smallInteger('failure_count')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('purge_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'is_active']);
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_webhook_endpoints_events ON webhook_endpoints USING GIN (events)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
