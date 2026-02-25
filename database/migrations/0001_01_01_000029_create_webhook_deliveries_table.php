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

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('webhook_endpoint_id');
            $table->string('event', 100);
            $table->json('payload');
            $table->smallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->smallInteger('attempts')->default(0);
            $table->smallInteger('max_attempts')->default(4);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('webhook_endpoint_id')->references('id')->on('webhook_endpoints')->cascadeOnDelete();
            $table->index(['webhook_endpoint_id', 'created_at']);
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_webhook_deliveries_pending ON webhook_deliveries (next_retry_at) WHERE delivered_at IS NULL AND failed_at IS NULL');
        } else {
            Schema::table('webhook_deliveries', function (Blueprint $table) {
                $table->index('next_retry_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
