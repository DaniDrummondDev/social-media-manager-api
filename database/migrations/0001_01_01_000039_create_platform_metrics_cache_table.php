<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_metrics_cache', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value');
            $table->timestamp('computed_at')->useCurrent();
            $table->integer('ttl_seconds')->default(300);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_metrics_cache');
    }
};
