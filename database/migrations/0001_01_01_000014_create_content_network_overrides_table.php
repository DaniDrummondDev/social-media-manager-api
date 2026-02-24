<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_network_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('content_id');
            $table->string('provider', 20);
            $table->string('title', 500)->nullable();
            $table->text('body')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();

            $table->unique(['content_id', 'provider']);
            $table->index('content_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_network_overrides');
    }
};
