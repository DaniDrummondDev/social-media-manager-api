<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('created_by');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('tags')->default('[]');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('purge_at')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['organization_id', 'starts_at', 'ends_at']);
            $table->index('purge_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX uq_campaigns_org_name ON campaigns (organization_id, LOWER(name)) WHERE deleted_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
