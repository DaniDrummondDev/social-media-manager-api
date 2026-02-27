<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('connected_by');
            $table->string('provider', 20);
            $table->string('provider_account_id', 255);
            $table->string('provider_account_name', 255);
            $table->text('encrypted_access_token');
            $table->text('encrypted_refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->default('[]');
            $table->string('status', 20)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamp('connected_at');
            $table->timestamps();

            $table->unique(['organization_id', 'provider', 'provider_account_id']);
            $table->index('status');
            $table->index('token_expires_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('connected_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
