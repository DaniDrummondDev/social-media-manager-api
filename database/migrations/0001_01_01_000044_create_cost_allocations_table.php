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

        Schema::create('cost_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->uuid('organization_id');
            $table->string('resource_type', 30);
            $table->uuid('resource_id')->nullable();
            $table->text('description');
            $table->bigInteger('cost_cents');
            $table->string('currency', 3)->default('BRL');
            $table->timestamp('allocated_at');
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['client_id', 'allocated_at']);
            $table->index(['organization_id', 'allocated_at']);
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_costs_org_resource ON cost_allocations (organization_id, resource_type)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_allocations');
    }
};
