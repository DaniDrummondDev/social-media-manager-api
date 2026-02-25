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

        Schema::create('platform_admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('role', 20)->default('support');
            $table->json('permissions')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_platform_admins_active ON platform_admins (role) WHERE is_active = TRUE');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_admins');
    }
};
