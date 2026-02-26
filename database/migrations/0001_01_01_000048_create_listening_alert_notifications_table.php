<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_alert_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('alert_id');
            $table->uuid('organization_id');
            $table->string('channel', 20);
            $table->string('status', 20)->default('sent');
            $table->text('payload')->nullable();
            $table->timestamps();

            $table->foreign('alert_id')->references('id')->on('listening_alerts')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['alert_id', 'created_at']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_alert_notifications');
    }
};
