<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GetSafetyChecksInput;
use App\Application\AIIntelligence\DTOs\SafetyCheckOutput;
use App\Application\AIIntelligence\UseCases\GetSafetyChecksUseCase;
use App\Domain\AIIntelligence\Entities\BrandSafetyCheck;
use App\Domain\AIIntelligence\Repositories\BrandSafetyCheckRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\SafetyStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->checkRepository = Mockery::mock(BrandSafetyCheckRepositoryInterface::class);

    $this->useCase = new GetSafetyChecksUseCase(
        $this->checkRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->contentId = 'b1c2d3e4-f5a6-7890-bcde-f12345678901';
});

it('returns checks filtered by organization', function () {
    $check = BrandSafetyCheck::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        provider: null,
        overallStatus: SafetyStatus::Passed,
        overallScore: 100,
        checks: [],
        modelUsed: 'gpt-4',
        tokensInput: 200,
        tokensOutput: 80,
        checkedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
    );

    $this->checkRepository->shouldReceive('findByContentId')->once()->andReturn([$check]);

    $input = new GetSafetyChecksInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toHaveCount(1)
        ->and($output[0])->toBeInstanceOf(SafetyCheckOutput::class)
        ->and($output[0]->overallStatus)->toBe('passed')
        ->and($output[0]->overallScore)->toBe(100);
});

it('returns empty array when no checks', function () {
    $this->checkRepository->shouldReceive('findByContentId')->once()->andReturn([]);

    $input = new GetSafetyChecksInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeEmpty();
});

it('filters out checks from other organizations', function () {
    $differentOrgId = 'c3d4e5f6-a7b8-9012-cdef-345678901234';

    $ownCheck = BrandSafetyCheck::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::fromString($this->contentId),
        provider: null,
        overallStatus: SafetyStatus::Passed,
        overallScore: 100,
        checks: [],
        modelUsed: null,
        tokensInput: null,
        tokensOutput: null,
        checkedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
    );

    $otherCheck = BrandSafetyCheck::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($differentOrgId),
        contentId: Uuid::fromString($this->contentId),
        provider: null,
        overallStatus: SafetyStatus::Blocked,
        overallScore: 10,
        checks: [],
        modelUsed: null,
        tokensInput: null,
        tokensOutput: null,
        checkedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
    );

    $this->checkRepository->shouldReceive('findByContentId')->once()->andReturn([$ownCheck, $otherCheck]);

    $input = new GetSafetyChecksInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toHaveCount(1)
        ->and($output[0]->overallStatus)->toBe('passed');
});
