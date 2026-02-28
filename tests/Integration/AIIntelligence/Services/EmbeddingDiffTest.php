<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('should calculate cosine distance between two embeddings', function () {
    $embedding1 = generateNormalizedEmbedding(1536);
    $embedding2 = generateNormalizedEmbedding(1536);

    $distance = calculateCosineDistance($embedding1, $embedding2);

    expect($distance)->toBeFloat()
        ->and($distance)->toBeGreaterThanOrEqual(0)
        ->and($distance)->toBeLessThanOrEqual(2);
});

it('should calculate zero distance for identical embeddings', function () {
    $embedding = generateNormalizedEmbedding(1536);

    $distance = calculateCosineDistance($embedding, $embedding);

    expect($distance)->toBeLessThan(0.001);
});

it('should use pgvector for similarity search', function () {
    $driver = DB::getDriverName();

    if ($driver !== 'pgsql') {
        expect(true)->toBeTrue();
        return;
    }

    $hasVectorExtension = DB::select("
        SELECT EXISTS (
            SELECT 1 FROM pg_extension WHERE extname = 'vector'
        ) as exists
    ");

    if (! $hasVectorExtension[0]->exists) {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
    }

    DB::statement('
        CREATE TABLE IF NOT EXISTS test_embeddings (
            id UUID PRIMARY KEY,
            content_id UUID NOT NULL,
            embedding vector(1536)
        )
    ');

    $contentId1 = Uuid::generate();
    $contentId2 = Uuid::generate();

    $embedding1 = generateNormalizedEmbedding(1536);
    $embedding2 = generateNormalizedEmbedding(1536);

    DB::table('test_embeddings')->insert([
        'id' => (string) Uuid::generate(),
        'content_id' => (string) $contentId1,
        'embedding' => '[' . implode(',', $embedding1) . ']',
    ]);

    DB::table('test_embeddings')->insert([
        'id' => (string) Uuid::generate(),
        'content_id' => (string) $contentId2,
        'embedding' => '[' . implode(',', $embedding2) . ']',
    ]);

    $queryEmbedding = '[' . implode(',', $embedding1) . ']';

    $results = DB::select("
        SELECT
            content_id,
            embedding <=> ? as distance
        FROM test_embeddings
        ORDER BY embedding <=> ?
        LIMIT 5
    ", [$queryEmbedding, $queryEmbedding]);

    expect($results)->not->toBeEmpty()
        ->and($results[0]->distance)->toBeFloat()
        ->and($results[0]->distance)->toBeGreaterThanOrEqual(0);

    DB::statement('DROP TABLE test_embeddings');
});

it('should find similar content using cosine similarity', function () {
    $driver = DB::getDriverName();

    if ($driver !== 'pgsql') {
        expect(true)->toBeTrue();
        return;
    }

    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

    DB::statement('
        CREATE TABLE IF NOT EXISTS test_content_embeddings (
            id UUID PRIMARY KEY,
            organization_id UUID NOT NULL,
            content TEXT NOT NULL,
            embedding vector(1536)
        )
    ');

    $orgId = Uuid::generate();

    $similarEmbedding = array_fill(0, 1536, 0.1);
    $differentEmbedding = array_fill(0, 1536, -0.1);

    DB::table('test_content_embeddings')->insert([
        'id' => (string) Uuid::generate(),
        'organization_id' => (string) $orgId,
        'content' => 'Marketing tips for social media',
        'embedding' => '[' . implode(',', $similarEmbedding) . ']',
    ]);

    DB::table('test_content_embeddings')->insert([
        'id' => (string) Uuid::generate(),
        'organization_id' => (string) $orgId,
        'content' => 'Social media marketing strategies',
        'embedding' => '[' . implode(',', $similarEmbedding) . ']',
    ]);

    DB::table('test_content_embeddings')->insert([
        'id' => (string) Uuid::generate(),
        'organization_id' => (string) $orgId,
        'content' => 'Cooking recipes for beginners',
        'embedding' => '[' . implode(',', $differentEmbedding) . ']',
    ]);

    $queryEmbedding = '[' . implode(',', $similarEmbedding) . ']';

    $results = DB::select("
        SELECT
            content,
            embedding <=> ? as distance
        FROM test_content_embeddings
        WHERE organization_id = ?
        ORDER BY embedding <=> ?
        LIMIT 3
    ", [$queryEmbedding, (string) $orgId, $queryEmbedding]);

    expect($results)->toHaveCount(3)
        ->and($results[0]->distance)->toBeLessThan($results[2]->distance);

    DB::statement('DROP TABLE test_content_embeddings');
});

it('should calculate L2 distance between embeddings', function () {
    $driver = DB::getDriverName();

    if ($driver !== 'pgsql') {
        expect(true)->toBeTrue();
        return;
    }

    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

    DB::statement('
        CREATE TABLE IF NOT EXISTS test_l2_embeddings (
            id UUID PRIMARY KEY,
            embedding vector(128)
        )
    ');

    $embedding1 = array_fill(0, 128, 0.5);
    $embedding2 = array_fill(0, 128, 0.6);

    $id1 = Uuid::generate();
    $id2 = Uuid::generate();

    DB::table('test_l2_embeddings')->insert([
        'id' => (string) $id1,
        'embedding' => '[' . implode(',', $embedding1) . ']',
    ]);

    DB::table('test_l2_embeddings')->insert([
        'id' => (string) $id2,
        'embedding' => '[' . implode(',', $embedding2) . ']',
    ]);

    $queryEmbedding = '[' . implode(',', $embedding1) . ']';

    $results = DB::select("
        SELECT
            id,
            embedding <-> ? as l2_distance
        FROM test_l2_embeddings
        ORDER BY embedding <-> ?
        LIMIT 2
    ", [$queryEmbedding, $queryEmbedding]);

    expect($results)->toHaveCount(2)
        ->and($results[0]->l2_distance)->toBeLessThan($results[1]->l2_distance);

    DB::statement('DROP TABLE test_l2_embeddings');
});

it('should calculate inner product similarity', function () {
    $driver = DB::getDriverName();

    if ($driver !== 'pgsql') {
        expect(true)->toBeTrue();
        return;
    }

    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

    DB::statement('
        CREATE TABLE IF NOT EXISTS test_ip_embeddings (
            id UUID PRIMARY KEY,
            embedding vector(256)
        )
    ');

    $embedding1 = generateNormalizedEmbedding(256);
    $embedding2 = generateNormalizedEmbedding(256);

    DB::table('test_ip_embeddings')->insert([
        'id' => (string) Uuid::generate(),
        'embedding' => '[' . implode(',', $embedding1) . ']',
    ]);

    DB::table('test_ip_embeddings')->insert([
        'id' => (string) Uuid::generate(),
        'embedding' => '[' . implode(',', $embedding2) . ']',
    ]);

    $queryEmbedding = '[' . implode(',', $embedding1) . ']';

    $results = DB::select("
        SELECT
            id,
            (embedding <#> ?) * -1 as similarity
        FROM test_ip_embeddings
        ORDER BY embedding <#> ?
        LIMIT 2
    ", [$queryEmbedding, $queryEmbedding]);

    expect($results)->toHaveCount(2)
        ->and($results[0]->similarity)->toBeFloat();

    DB::statement('DROP TABLE test_ip_embeddings');
});

it('should handle embeddings with different dimensions', function () {
    $embedding384 = generateNormalizedEmbedding(384);
    $embedding1536 = generateNormalizedEmbedding(1536);

    expect(count($embedding384))->toBe(384)
        ->and(count($embedding1536))->toBe(1536);
});

it('should normalize embeddings for cosine similarity', function () {
    $embedding = [3.0, 4.0, 0.0];
    $normalized = normalizeEmbedding($embedding);

    $magnitude = sqrt(
        array_sum(
            array_map(fn ($v) => $v * $v, $normalized)
        )
    );

    // Magnitude should be approximately 1.0 (within 0.001)
    expect(abs($magnitude - 1.0))->toBeLessThan(0.001);
});

function calculateCosineDistance(array $embedding1, array $embedding2): float
{
    $dotProduct = 0.0;
    $magnitude1 = 0.0;
    $magnitude2 = 0.0;

    for ($i = 0; $i < count($embedding1); $i++) {
        $dotProduct += $embedding1[$i] * $embedding2[$i];
        $magnitude1 += $embedding1[$i] * $embedding1[$i];
        $magnitude2 += $embedding2[$i] * $embedding2[$i];
    }

    $magnitude1 = sqrt($magnitude1);
    $magnitude2 = sqrt($magnitude2);

    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 2.0;
    }

    $cosineSimilarity = $dotProduct / ($magnitude1 * $magnitude2);

    return 1.0 - $cosineSimilarity;
}

function generateNormalizedEmbedding(int $dimensions): array
{
    $embedding = [];
    for ($i = 0; $i < $dimensions; $i++) {
        $embedding[] = (mt_rand() / mt_getrandmax()) * 2 - 1;
    }

    return normalizeEmbedding($embedding);
}

function normalizeEmbedding(array $embedding): array
{
    $magnitude = sqrt(
        array_sum(
            array_map(fn ($v) => $v * $v, $embedding)
        )
    );

    if ($magnitude == 0) {
        return $embedding;
    }

    return array_map(fn ($v) => $v / $magnitude, $embedding);
}
