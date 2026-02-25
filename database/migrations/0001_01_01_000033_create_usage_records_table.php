<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('resource_type', 30);
            $table->integer('quantity')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('recorded_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'resource_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
