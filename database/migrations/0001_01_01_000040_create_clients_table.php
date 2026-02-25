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

        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('tax_id', 20)->nullable();
            $table->string('tax_id_type', 4)->nullable();
            $table->jsonb('billing_address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('purge_at')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'status']);
        });

        if ($driver === 'pgsql') {
            DB::statement("CREATE INDEX idx_clients_org_active ON clients (organization_id) WHERE status = 'active' AND deleted_at IS NULL");
            DB::statement('CREATE INDEX idx_clients_org_search ON clients USING gin (to_tsvector(\'portuguese\', name || \' \' || COALESCE(company_name, \'\')))');
            DB::statement('CREATE UNIQUE INDEX uq_clients_org_tax_id ON clients (organization_id, tax_id) WHERE tax_id IS NOT NULL AND deleted_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
