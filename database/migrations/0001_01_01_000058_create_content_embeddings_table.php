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

        if ($driver === 'pgsql') {
            // Ensure pgvector extension is enabled
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

            // Create table with vector column for embeddings
            DB::statement('
                CREATE TABLE content_embeddings (
                    id UUID PRIMARY KEY,
                    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
                    content_id UUID NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
                    embedding vector(1536) NOT NULL,
                    model_used VARCHAR(50) NOT NULL DEFAULT \'text-embedding-3-small\',
                    tokens_used INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ');

            // Create indexes for pgvector similarity search
            DB::statement('CREATE INDEX idx_ce_organization_id ON content_embeddings (organization_id)');
            DB::statement('CREATE UNIQUE INDEX idx_ce_content_id ON content_embeddings (content_id)');

            // Create IVFFlat index for approximate nearest neighbor search
            // Lists = sqrt(expected_rows), assuming ~10000 contents per org initially
            DB::statement('CREATE INDEX idx_ce_embedding_ivfflat ON content_embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        } else {
            // SQLite/MySQL fallback - store embedding as JSON text (for testing only)
            Schema::create('content_embeddings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_id');
                $table->uuid('content_id');
                $table->text('embedding'); // JSON-encoded array
                $table->string('model_used', 50)->default('text-embedding-3-small');
                $table->integer('tokens_used')->default(0);
                $table->timestamps();

                $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
                $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();
                $table->unique('content_id');
                $table->index('organization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_embeddings');
    }
};
