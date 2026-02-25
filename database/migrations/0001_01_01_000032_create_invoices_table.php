<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('subscription_id');
            $table->string('external_invoice_id', 255)->unique();
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('status', 20)->default('open');
            $table->string('invoice_url', 2000)->nullable();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
