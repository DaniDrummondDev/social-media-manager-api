<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('crm_connection_id');
            $table->string('direction', 10);
            $table->string('entity_type', 20);
            $table->string('action', 30);
            $table->string('status', 20);
            $table->string('external_id', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['crm_connection_id', 'created_at']);
            $table->index(['organization_id', 'status']);

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('crm_connection_id')->references('id')->on('crm_connections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_sync_logs');
    }
};
