<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('connected_by');
            $table->string('provider', 20);
            $table->string('provider_user_id', 255);
            $table->string('username', 255);
            $table->string('display_name', 255)->nullable();
            $table->string('profile_picture_url', 1000)->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->default('[]');
            $table->string('status', 30)->default('connected');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('connected_at');
            $table->timestamp('disconnected_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('purge_at')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('connected_by')->references('id')->on('users');

            $table->index('organization_id');
            $table->index('status');
            $table->index('token_expires_at');
            $table->index(['provider', 'status']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX uq_social_accounts_org_provider_user ON social_accounts (organization_id, provider, provider_user_id) WHERE deleted_at IS NULL');
        } else {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->unique(['organization_id', 'provider', 'provider_user_id'], 'uq_social_accounts_org_provider_user');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
