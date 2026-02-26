<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;
use App\Application\AIIntelligence\DTOs\ContentRecommendationsOutput;
use App\Application\AIIntelligence\DTOs\GetContentRecommendationsInput;
use App\Application\AIIntelligence\Exceptions\ContentProfileNotFoundException;
use App\Application\AIIntelligence\UseCases\GetContentRecommendationsUseCase;
use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;
use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->profileRepository = Mockery::mock(ContentProfileRepositoryInterface::class);
    $this->embeddingGenerator = Mockery::mock(EmbeddingGeneratorInterface::class);
    $this->similaritySearch = Mockery::mock(SimilaritySearchInterface::class);
    $this->useCase = new GetContentRecommendationsUseCase(
        $this->profileRepository,
        $this->embeddingGenerator,
        $this->similaritySearch,
    );
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns recommendations based on similar content', function () {
    $profile = ContentProfile::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        provider: 'instagram',
        totalContentsAnalyzed: 100,
        topThemes: [['theme' => 'tech', 'score' => 0.9, 'content_count' => 50]],
        engagementPatterns: EngagementPattern::create(120, 30, 15, ['reel', 'carousel']),
        contentFingerprint: ContentFingerprint::create(280, ['#tech'], ['informative' => 0.7], 3.5),
        highPerformerTraits: [],
        centroidEmbedding: [0.1, 0.2, 0.3],
        status: ProfileStatus::Generated,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->profileRepository->shouldReceive('findByOrganization')
        ->once()
        ->andReturn($profile);

    $topicEmbedding = array_fill(0, 1536, 0.5);
    $this->embeddingGenerator->shouldReceive('generate')
        ->once()
        ->with('AI trends')
        ->andReturn($topicEmbedding);

    $this->similaritySearch->shouldReceive('findSimilar')
        ->once()
        ->andReturn([
            ['content_id' => 'c-1', 'similarity' => 0.85],
        ]);

    $input = new GetContentRecommendationsInput(
        organizationId: $this->orgId,
        topic: 'AI trends',
        limit: 5,
        provider: 'instagram',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ContentRecommendationsOutput::class)
        ->and($output->recommendations)->toHaveCount(1)
        ->and($output->recommendations[0]['topic'])->toBe('AI trends')
        ->and($output->recommendations[0]['similarity_score'])->toBe(0.85)
        ->and($output->recommendations[0]['suggested_format'])->toBe('reel');
});

it('throws when profile not found', function () {
    $this->profileRepository->shouldReceive('findByOrganization')
        ->once()
        ->andReturn(null);

    $input = new GetContentRecommendationsInput(
        organizationId: $this->orgId,
        topic: 'anything',
    );

    $this->useCase->execute($input);
})->throws(ContentProfileNotFoundException::class);

it('throws when profile has no centroid embedding', function () {
    $profile = ContentProfile::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        provider: null,
        totalContentsAnalyzed: 50,
        topThemes: [],
        engagementPatterns: null,
        contentFingerprint: null,
        highPerformerTraits: [],
        centroidEmbedding: null,
        status: ProfileStatus::Generated,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->profileRepository->shouldReceive('findByOrganization')
        ->once()
        ->andReturn($profile);

    $input = new GetContentRecommendationsInput(
        organizationId: $this->orgId,
        topic: 'test',
    );

    $this->useCase->execute($input);
})->throws(ContentProfileNotFoundException::class);
