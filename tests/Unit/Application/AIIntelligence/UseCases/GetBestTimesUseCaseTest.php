<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\BestTimesOutput;
use App\Application\AIIntelligence\DTOs\GetBestTimesInput;
use App\Application\AIIntelligence\UseCases\GetBestTimesUseCase;
use App\Domain\AIIntelligence\Entities\PostingTimeRecommendation;
use App\Domain\AIIntelligence\Repositories\PostingTimeRecommendationRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\AIIntelligence\ValueObjects\TimeSlotScore;
use App\Domain\AIIntelligence\ValueObjects\TopSlot;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->recommendationRepository = Mockery::mock(PostingTimeRecommendationRepositoryInterface::class);

    $this->useCase = new GetBestTimesUseCase(
        $this->recommendationRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns BestTimesOutput when recommendation found', function () {
    $now = new DateTimeImmutable;

    $recommendation = PostingTimeRecommendation::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        provider: 'instagram',
        heatmap: [TimeSlotScore::create(1, 9, 85)],
        topSlots: [TopSlot::create(1, 9, 4.5, 30)],
        worstSlots: [TopSlot::create(0, 3, 0.2, 30)],
        sampleSize: 60,
        confidenceLevel: ConfidenceLevel::High,
        calculatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
    );

    $this->recommendationRepository->shouldReceive('findByOrganization')->once()->andReturn($recommendation);

    $input = new GetBestTimesInput(
        organizationId: $this->orgId,
        provider: 'instagram',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(BestTimesOutput::class)
        ->and($output->confidenceLevel)->toBe('high')
        ->and($output->sampleSize)->toBe(60)
        ->and($output->topSlots)->toHaveCount(1)
        ->and($output->worstSlots)->toHaveCount(1);
});

it('returns null when no recommendation', function () {
    $this->recommendationRepository->shouldReceive('findByOrganization')->once()->andReturn(null);

    $input = new GetBestTimesInput(
        organizationId: $this->orgId,
        provider: 'instagram',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeNull();
});
