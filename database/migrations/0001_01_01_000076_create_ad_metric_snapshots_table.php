<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_metric_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('boost_id');
            $table->string('period', 20);
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->integer('spend_cents')->default(0);
            $table->string('spend_currency', 3);
            $table->integer('conversions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('cpc', 10, 4)->nullable();
            $table->decimal('cpm', 10, 4)->nullable();
            $table->decimal('cost_per_conversion', 10, 4)->nullable();
            $table->timestamp('captured_at');

            $table->index('boost_id');
            $table->index(['boost_id', 'period']);
            $table->index('captured_at');

            $table->foreign('boost_id')->references('id')->on('ad_boosts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_metric_snapshots');
    }
};
