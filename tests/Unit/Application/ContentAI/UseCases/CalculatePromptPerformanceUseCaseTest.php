<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\CalculatePromptPerformanceInput;
use App\Application\ContentAI\UseCases\CalculatePromptPerformanceUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\ContentAI\Entities\PromptTemplate;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->templateRepository = Mockery::mock(PromptTemplateRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CalculatePromptPerformanceUseCase(
        $this->templateRepository,
        $this->eventDispatcher,
    );
});

it('recalculates performance for active templates with uses', function () {
    $now = new DateTimeImmutable;

    $template = PromptTemplate::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Test',
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        variables: [],
        isActive: true,
        isDefault: false,
        performanceScore: null,
        totalUses: 10,
        totalAccepted: 8,
        totalEdited: 2,
        totalRejected: 0,
        createdBy: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $this->templateRepository->shouldReceive('findAllActive')->once()->andReturn([$template]);
    $this->templateRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new CalculatePromptPerformanceInput(
        userId: 'system',
    ));
});

it('skips templates with zero uses', function () {
    $now = new DateTimeImmutable;

    $template = PromptTemplate::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Unused',
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

    $this->templateRepository->shouldReceive('findAllActive')->once()->andReturn([$template]);
    $this->templateRepository->shouldNotReceive('update');
    $this->eventDispatcher->shouldNotReceive('dispatch');

    $this->useCase->execute(new CalculatePromptPerformanceInput(
        userId: 'system',
    ));
});
