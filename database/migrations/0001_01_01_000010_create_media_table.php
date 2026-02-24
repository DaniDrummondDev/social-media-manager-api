<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('uploaded_by');
            $table->string('file_name', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('storage_path', 1000);
            $table->string('thumbnail_path', 1000)->nullable();
            $table->string('disk', 50)->default('spaces');
            $table->string('checksum', 64);
            $table->string('scan_status', 20)->default('pending');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('purge_at')->nullable();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users');

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'mime_type']);
            $table->index('scan_status');
            $table->index('purge_at');
            $table->index(['organization_id', 'checksum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
