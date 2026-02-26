<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_field_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crm_connection_id');
            $table->string('smm_field', 100);
            $table->string('crm_field', 100);
            $table->string('transform', 50)->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['crm_connection_id', 'smm_field']);

            $table->foreign('crm_connection_id')->references('id')->on('crm_connections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_field_mappings');
    }
};
