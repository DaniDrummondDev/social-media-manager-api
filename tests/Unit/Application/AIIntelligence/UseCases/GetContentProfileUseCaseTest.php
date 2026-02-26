<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\ContentProfileOutput;
use App\Application\AIIntelligence\DTOs\GetContentProfileInput;
use App\Application\AIIntelligence\Exceptions\ContentProfileNotFoundException;
use App\Application\AIIntelligence\UseCases\GetContentProfileUseCase;
use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;
use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->profileRepository = Mockery::mock(ContentProfileRepositoryInterface::class);
    $this->useCase = new GetContentProfileUseCase($this->profileRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns a content profile when found', function () {
    $profile = ContentProfile::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        provider: 'instagram',
        totalContentsAnalyzed: 100,
        topThemes: [['theme' => 'tech', 'score' => 0.9, 'content_count' => 50]],
        engagementPatterns: EngagementPattern::create(120, 30, 15, ['reel']),
        contentFingerprint: ContentFingerprint::create(280, ['#tech'], ['informative' => 0.7], 3.5),
        highPerformerTraits: ['short_captions'],
        centroidEmbedding: [0.1, 0.2],
        status: ProfileStatus::Generated,
        generatedAt: new DateTimeImmutable('-1 day'),
        expiresAt: new DateTimeImmutable('+6 days'),
        createdAt: new DateTimeImmutable('-1 day'),
        updatedAt: new DateTimeImmutable('-1 day'),
    );

    $this->profileRepository->shouldReceive('findByOrganization')
        ->once()
        ->andReturn($profile);

    $input = new GetContentProfileInput(
        organizationId: $this->orgId,
        provider: 'instagram',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ContentProfileOutput::class)
        ->and($output->provider)->toBe('instagram')
        ->and($output->totalContentsAnalyzed)->toBe(100);
});

it('throws when profile not found', function () {
    $this->profileRepository->shouldReceive('findByOrganization')
        ->once()
        ->andReturn(null);

    $input = new GetContentProfileInput(
        organizationId: $this->orgId,
        provider: 'tiktok',
    );

    $this->useCase->execute($input);
})->throws(ContentProfileNotFoundException::class);
