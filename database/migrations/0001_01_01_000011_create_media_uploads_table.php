<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->string('file_name', 255);
            $table->string('mime_type', 100);
            $table->bigInteger('total_bytes');
            $table->integer('chunk_size_bytes');
            $table->integer('total_chunks');
            $table->json('received_chunks')->default('[]');
            $table->string('s3_upload_id', 255)->nullable();
            $table->json('s3_parts')->default('{}');
            $table->string('status', 20)->default('initiated');
            $table->string('checksum', 64)->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');

            $table->index('organization_id');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_uploads');
    }
};
