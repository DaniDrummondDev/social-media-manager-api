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

        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->integer('price_monthly_cents')->default(0);
            $table->integer('price_yearly_cents')->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->json('limits')->default('{}');
            $table->json('features')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->string('stripe_price_monthly_id', 255)->nullable();
            $table->string('stripe_price_yearly_id', 255)->nullable();
            $table->timestamps();
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_plans_active ON plans (sort_order) WHERE is_active = TRUE');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
