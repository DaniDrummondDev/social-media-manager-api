<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_invoice_id');
            $table->text('description');
            $table->integer('quantity');
            $table->bigInteger('unit_price_cents');
            $table->bigInteger('total_cents');
            $table->smallInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('client_invoice_id')->references('id')->on('client_invoices')->cascadeOnDelete();
            $table->index('client_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_invoice_items');
    }
};
