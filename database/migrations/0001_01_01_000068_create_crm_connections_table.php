<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('provider', 30);
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('external_account_id', 255);
            $table->string('account_name', 255);
            $table->string('status', 20)->default('connected');
            $table->json('settings')->default('{}');
            $table->uuid('connected_by');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->timestamp('disconnected_at')->nullable();

            $table->unique(['organization_id', 'provider']);
            $table->index('status');

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('connected_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_connections');
    }
};
