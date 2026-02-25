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

        Schema::create('client_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->uuid('organization_id');
            $table->string('name', 255);
            $table->string('type', 30);
            $table->bigInteger('value_cents');
            $table->string('currency', 3)->default('BRL');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->jsonb('social_account_ids')->default('[]');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['client_id', 'status']);
            $table->index(['organization_id', 'status']);
        });

        if ($driver === 'pgsql') {
            DB::statement("CREATE INDEX idx_contracts_client_active ON client_contracts (client_id) WHERE status = 'active'");
            DB::statement("CREATE INDEX idx_contracts_org_active ON client_contracts (organization_id) WHERE status = 'active'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contracts');
    }
};
