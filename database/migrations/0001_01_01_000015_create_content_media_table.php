<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('content_id');
            $table->uuid('media_id');
            $table->smallInteger('position')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();
            $table->foreign('media_id')->references('id')->on('media')->restrictOnDelete();

            $table->unique(['content_id', 'media_id']);
            $table->unique(['content_id', 'position']);
            $table->index(['content_id', 'position']);
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_media');
    }
};
