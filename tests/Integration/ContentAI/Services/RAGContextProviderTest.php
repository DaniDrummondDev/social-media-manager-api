<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\ContentAI\Contracts\RAGContextProviderInterface;
use App\Infrastructure\ContentAI\Services\EloquentRAGContextProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->orgId = (string) Str::uuid();
    $this->userId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'rag-test-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'rag-test-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    // Mock embedding generator
    $this->embeddingGenerator = Mockery::mock(EmbeddingGeneratorInterface::class);
    $this->embeddingGenerator->shouldReceive('generate')
        ->andReturn(array_fill(0, 1536, 0.1));

    $this->provider = new EloquentRAGContextProvider($this->embeddingGenerator);
});

it('returns empty result when not enough embeddings exist', function () {
    $result = $this->provider->retrieve($this->orgId, 'productivity tips', null, 5);

    expect($result->contentIds)->toBeEmpty()
        ->and($result->formattedExamples)->toBe('')
        ->and($result->tokenCount)->toBe(0);
});

it('returns empty result via static empty method', function () {
    $result = \App\Application\ContentAI\DTOs\RAGContextResult::empty();

    expect($result->isEmpty())->toBeTrue()
        ->and($result->contentIds)->toBeEmpty()
        ->and($result->tokenCount)->toBe(0);
});

it('resolves provider from container', function () {
    $provider = app(RAGContextProviderInterface::class);

    expect($provider)->toBeInstanceOf(EloquentRAGContextProvider::class);
});
