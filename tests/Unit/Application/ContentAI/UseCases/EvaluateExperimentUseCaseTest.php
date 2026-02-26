<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\EvaluateExperimentInput;
use App\Application\ContentAI\DTOs\PromptExperimentOutput;
use App\Application\ContentAI\UseCases\EvaluateExperimentUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\PromptExperimentRepositoryInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\ContentAI\Entities\PromptExperiment;
use App\Domain\ContentAI\Entities\PromptTemplate;
use App\Domain\ContentAI\ValueObjects\ExperimentStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->experimentRepository = Mockery::mock(PromptExperimentRepositoryInterface::class);
    $this->templateRepository = Mockery::mock(PromptTemplateRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new EvaluateExperimentUseCase(
        $this->experimentRepository,
        $this->templateRepository,
        $this->eventDispatcher,
    );
});

it('evaluates experiment with high confidence and activates winner', function () {
    $variantA = Uuid::generate();
    $variantB = Uuid::generate();
    $now = new DateTimeImmutable;

    // 80% vs 40% acceptance → high confidence → completes
    $experiment = PromptExperiment::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        status: ExperimentStatus::Running,
        variantAId: $variantA,
        variantBId: $variantB,
        trafficSplit: 0.5,
        minSampleSize: 50,
        variantAUses: 100,
        variantAAccepted: 80,
        variantBUses: 100,
        variantBAccepted: 40,
        winnerId: null,
        confidenceLevel: null,
        startedAt: $now,
        completedAt: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $loserTemplate = PromptTemplate::reconstitute(
        id: $variantB,
        organizationId: Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Loser',
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        variables: [],
        isActive: true,
        isDefault: false,
        performanceScore: null,
        totalUses: 0,
        totalAccepted: 0,
        totalEdited: 0,
        totalRejected: 0,
        createdBy: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $this->experimentRepository->shouldReceive('findById')->once()->andReturn($experiment);
    $this->templateRepository->shouldReceive('findById')->once()->andReturn($loserTemplate);
    $this->templateRepository->shouldReceive('update')->once();
    $this->experimentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new EvaluateExperimentInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        experimentId: (string) $experiment->id,
    ));

    expect($output)->toBeInstanceOf(PromptExperimentOutput::class)
        ->and($output->status)->toBe('completed')
        ->and($output->winnerId)->toBe((string) $variantA);
});

it('returns unchanged experiment when confidence is insufficient', function () {
    $now = new DateTimeImmutable;

    // 50% vs 48% → low confidence → stays running
    $experiment = PromptExperiment::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        status: ExperimentStatus::Running,
        variantAId: Uuid::generate(),
        variantBId: Uuid::generate(),
        trafficSplit: 0.5,
        minSampleSize: 50,
        variantAUses: 50,
        variantAAccepted: 25,
        variantBUses: 50,
        variantBAccepted: 24,
        winnerId: null,
        confidenceLevel: null,
        startedAt: $now,
        completedAt: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $this->experimentRepository->shouldReceive('findById')->once()->andReturn($experiment);
    $this->experimentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch');

    $output = $this->useCase->execute(new EvaluateExperimentInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        experimentId: (string) $experiment->id,
    ));

    expect($output->status)->toBe('running')
        ->and($output->winnerId)->toBeNull();
});

it('throws when experiment not found', function () {
    $this->experimentRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new EvaluateExperimentInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        experimentId: (string) Uuid::generate(),
    ));
})->throws(DomainException::class, 'Prompt experiment not found');
