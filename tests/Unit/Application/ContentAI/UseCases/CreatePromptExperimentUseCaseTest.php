<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\CreatePromptExperimentInput;
use App\Application\ContentAI\DTOs\PromptExperimentOutput;
use App\Application\ContentAI\UseCases\CreatePromptExperimentUseCase;
use App\Domain\ContentAI\Contracts\PromptExperimentRepositoryInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\ContentAI\Entities\PromptTemplate;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->experimentRepository = Mockery::mock(PromptExperimentRepositoryInterface::class);
    $this->templateRepository = Mockery::mock(PromptTemplateRepositoryInterface::class);

    $this->useCase = new CreatePromptExperimentUseCase(
        $this->experimentRepository,
        $this->templateRepository,
    );

    $this->orgId = (string) Uuid::generate();
    $this->variantAId = Uuid::generate();
    $this->variantBId = Uuid::generate();
});

function makeTemplate(Uuid $id): PromptTemplate
{
    $now = new DateTimeImmutable;

    return PromptTemplate::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Template',
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
}

it('creates an experiment when no running experiment exists', function () {
    $this->experimentRepository->shouldReceive('hasRunningExperiment')->once()->andReturn(false);
    $this->templateRepository->shouldReceive('findById')
        ->with(Mockery::on(fn ($id) => (string) $id === (string) $this->variantAId))
        ->once()
        ->andReturn(makeTemplate($this->variantAId));
    $this->templateRepository->shouldReceive('findById')
        ->with(Mockery::on(fn ($id) => (string) $id === (string) $this->variantBId))
        ->once()
        ->andReturn(makeTemplate($this->variantBId));
    $this->experimentRepository->shouldReceive('create')->once();

    $output = $this->useCase->execute(new CreatePromptExperimentInput(
        organizationId: $this->orgId,
        userId: (string) Uuid::generate(),
        generationType: 'title',
        name: 'A/B Title Test',
        variantAId: (string) $this->variantAId,
        variantBId: (string) $this->variantBId,
    ));

    expect($output)->toBeInstanceOf(PromptExperimentOutput::class)
        ->and($output->status)->toBe('draft')
        ->and($output->name)->toBe('A/B Title Test');
});

it('throws when running experiment already exists', function () {
    $this->experimentRepository->shouldReceive('hasRunningExperiment')->once()->andReturn(true);

    $this->useCase->execute(new CreatePromptExperimentInput(
        organizationId: $this->orgId,
        userId: (string) Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        variantAId: (string) $this->variantAId,
        variantBId: (string) $this->variantBId,
    ));
})->throws(DomainException::class, 'An experiment is already running');

it('throws when variant A template not found', function () {
    $this->experimentRepository->shouldReceive('hasRunningExperiment')->once()->andReturn(false);
    $this->templateRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new CreatePromptExperimentInput(
        organizationId: $this->orgId,
        userId: (string) Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        variantAId: (string) $this->variantAId,
        variantBId: (string) $this->variantBId,
    ));
})->throws(DomainException::class, 'Prompt template not found');
