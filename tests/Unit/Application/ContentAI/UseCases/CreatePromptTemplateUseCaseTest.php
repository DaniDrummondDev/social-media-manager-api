<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\CreatePromptTemplateInput;
use App\Application\ContentAI\DTOs\PromptTemplateOutput;
use App\Application\ContentAI\UseCases\CreatePromptTemplateUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->templateRepository = Mockery::mock(PromptTemplateRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CreatePromptTemplateUseCase(
        $this->templateRepository,
        $this->eventDispatcher,
    );
});

it('creates a prompt template and returns output', function () {
    $this->templateRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new CreatePromptTemplateInput(
        userId: (string) Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Title Template',
        systemPrompt: 'You are a social media expert.',
        userPromptTemplate: 'Write a title for: {topic}',
        variables: ['topic'],
        organizationId: (string) Uuid::generate(),
    ));

    expect($output)->toBeInstanceOf(PromptTemplateOutput::class)
        ->and($output->generationType)->toBe('title')
        ->and($output->version)->toBe('v1')
        ->and($output->name)->toBe('Title Template')
        ->and($output->isActive)->toBeTrue()
        ->and($output->totalUses)->toBe(0);
});

it('creates system template with null organizationId', function () {
    $this->templateRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new CreatePromptTemplateInput(
        userId: (string) Uuid::generate(),
        generationType: 'description',
        version: 'v1',
        name: 'System Description Template',
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        organizationId: null,
    ));

    expect($output->organizationId)->toBeNull();
});
