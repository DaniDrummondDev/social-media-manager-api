<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_conversion_attributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('crm_connection_id');
            $table->uuid('content_id');
            $table->string('crm_entity_type', 50);
            $table->string('crm_entity_id', 255);
            $table->string('attribution_type', 30);
            $table->decimal('attribution_value', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('crm_stage', 100)->nullable();
            $table->json('interaction_data')->default('{}');
            $table->timestamp('attributed_at');
            $table->timestamp('created_at');

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('crm_connection_id')
                ->references('id')->on('crm_connections')
                ->cascadeOnDelete();

            $table->index(['organization_id', 'attribution_type']);
            $table->index(['content_id', 'attribution_type']);
            $table->index(['organization_id', 'attributed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_conversion_attributions');
    }
};
