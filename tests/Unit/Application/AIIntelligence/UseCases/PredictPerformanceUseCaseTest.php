<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;
use App\Application\AIIntelligence\DTOs\PredictPerformanceInput;
use App\Application\AIIntelligence\DTOs\PredictionOutput;
use App\Application\AIIntelligence\Exceptions\InsufficientDataException;
use App\Application\AIIntelligence\UseCases\PredictPerformanceUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;
use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->predictionRepository = Mockery::mock(PerformancePredictionRepositoryInterface::class);
    $this->profileRepository = Mockery::mock(ContentProfileRepositoryInterface::class);
    $this->similaritySearch = Mockery::mock(SimilaritySearchInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->useCase = new PredictPerformanceUseCase(
        $this->predictionRepository,
        $this->profileRepository,
        $this->similaritySearch,
        $this->eventDispatcher,
    );
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->contentId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
});

it('creates predictions for single provider', function () {
    $profile = ContentProfile::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        provider: 'instagram',
        totalContentsAnalyzed: 100,
        topThemes: [],
        engagementPatterns: EngagementPattern::create(100, 20, 10, ['post']),
        contentFingerprint: ContentFingerprint::create(200, [], [], 1.0),
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

    $this->predictionRepository->shouldReceive('create')
        ->once()
        ->withArgs(fn (PerformancePrediction $p) => $p->provider === 'instagram');

    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new PredictPerformanceInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
        providers: ['instagram'],
        userId: 'user-1',
    );

    $outputs = $this->useCase->execute($input);

    expect($outputs)->toHaveCount(1)
        ->and($outputs[0])->toBeInstanceOf(PredictionOutput::class)
        ->and($outputs[0]->provider)->toBe('instagram')
        ->and($outputs[0]->overallScore)->toBeGreaterThanOrEqual(0)
        ->and($outputs[0]->overallScore)->toBeLessThanOrEqual(100);
});

it('creates predictions for multiple providers', function () {
    $makeProfile = fn (string $provider) => ContentProfile::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        provider: $provider,
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
        ->twice()
        ->andReturnUsing(fn ($orgId, $provider) => $makeProfile($provider));

    $this->predictionRepository->shouldReceive('create')->twice();
    $this->eventDispatcher->shouldReceive('dispatch')->twice();

    $input = new PredictPerformanceInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
        providers: ['instagram', 'tiktok'],
        userId: 'user-1',
    );

    $outputs = $this->useCase->execute($input);

    expect($outputs)->toHaveCount(2)
        ->and($outputs[0]->provider)->toBe('instagram')
        ->and($outputs[1]->provider)->toBe('tiktok');
});

it('throws when no profile exists for provider', function () {
    $this->profileRepository->shouldReceive('findByOrganization')
        ->once()
        ->andReturn(null);

    $input = new PredictPerformanceInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
        providers: ['youtube'],
        userId: 'user-1',
    );

    $this->useCase->execute($input);
})->throws(InsufficientDataException::class);
