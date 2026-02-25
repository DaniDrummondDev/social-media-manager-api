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

        Schema::create('admin_audit_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_id');
            $table->string('action', 100);
            $table->string('resource_type', 50);
            $table->uuid('resource_id')->nullable();
            $table->json('context')->default('{}');
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('admin_id')->references('id')->on('platform_admins')->restrictOnDelete();
            $table->index(['created_at']);
            $table->index(['admin_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        if ($driver === 'pgsql') {
            DB::statement('CREATE INDEX idx_admin_audit_resource ON admin_audit_entries (resource_type, resource_id, created_at DESC) WHERE resource_id IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_entries');
    }
};
