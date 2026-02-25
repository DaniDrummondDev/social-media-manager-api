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

        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_event_id', 255)->unique();
            $table->string('event_type', 100);
            $table->boolean('processed')->default(false);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_stripe_events_unprocessed ON stripe_webhook_events (created_at) WHERE processed = FALSE');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
