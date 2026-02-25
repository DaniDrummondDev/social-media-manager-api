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

        Schema::create('client_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->uuid('contract_id')->nullable();
            $table->uuid('organization_id');
            $table->string('reference_month', 7);
            $table->bigInteger('subtotal_cents');
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('total_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('status', 20)->default('draft');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->text('payment_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('contract_id')->references('id')->on('client_contracts')->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['client_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'due_date']);
        });

        if ($driver === 'pgsql') {
            DB::statement("CREATE INDEX idx_invoices_overdue ON client_invoices (due_date) WHERE status = 'sent'");
            DB::statement("CREATE UNIQUE INDEX uq_invoices_contract_month ON client_invoices (contract_id, reference_month) WHERE contract_id IS NOT NULL AND status != 'cancelled'");
            DB::statement("CREATE INDEX idx_invoices_org_paid ON client_invoices (organization_id, paid_at) WHERE status = 'paid'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_invoices');
    }
};
