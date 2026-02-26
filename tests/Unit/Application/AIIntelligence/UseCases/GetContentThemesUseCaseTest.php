<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\ContentThemesOutput;
use App\Application\AIIntelligence\DTOs\GetContentThemesInput;
use App\Application\AIIntelligence\Exceptions\ContentProfileNotFoundException;
use App\Application\AIIntelligence\UseCases\GetContentThemesUseCase;
use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;
use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->profileRepository = Mockery::mock(ContentProfileRepositoryInterface::class);
    $this->useCase = new GetContentThemesUseCase($this->profileRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns themes limited by input', function () {
    $themes = [
        ['theme' => 'tech', 'score' => 0.95, 'content_count' => 80],
        ['theme' => 'ai', 'score' => 0.85, 'content_count' => 50],
        ['theme' => 'design', 'score' => 0.70, 'content_count' => 30],
    ];

    $profile = ContentProfile::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        provider: 'instagram',
        totalContentsAnalyzed: 100,
        topThemes: $themes,
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

    $input = new GetContentThemesInput(
        organizationId: $this->orgId,
        provider: 'instagram',
        limit: 2,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ContentThemesOutput::class)
        ->and($output->themes)->toHaveCount(2)
        ->and($output->themes[0]['theme'])->toBe('tech')
        ->and($output->themes[1]['theme'])->toBe('ai');
});

it('throws when profile not found', function () {
    $this->profileRepository->shouldReceive('findByOrganization')
        ->once()
        ->andReturn(null);

    $input = new GetContentThemesInput(
        organizationId: $this->orgId,
        provider: 'instagram',
    );

    $this->useCase->execute($input);
})->throws(ContentProfileNotFoundException::class);
