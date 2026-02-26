<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\GenerationFeedbackOutput;
use App\Application\ContentAI\DTOs\RecordGenerationFeedbackInput;
use App\Application\ContentAI\UseCases\RecordGenerationFeedbackUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->feedbackRepository = Mockery::mock(GenerationFeedbackRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new RecordGenerationFeedbackUseCase(
        $this->feedbackRepository,
        $this->eventDispatcher,
    );
});

it('records accepted feedback and returns output', function () {
    $this->feedbackRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new RecordGenerationFeedbackInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        generationId: (string) Uuid::generate(),
        action: 'accepted',
        originalOutput: ['title' => 'Generated title'],
        generationType: 'title',
    ));

    expect($output)->toBeInstanceOf(GenerationFeedbackOutput::class)
        ->and($output->action)->toBe('accepted')
        ->and($output->editedOutput)->toBeNull()
        ->and($output->generationType)->toBe('title');
});

it('records edited feedback with editedOutput', function () {
    $this->feedbackRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new RecordGenerationFeedbackInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        generationId: (string) Uuid::generate(),
        action: 'edited',
        originalOutput: ['title' => 'Original'],
        editedOutput: ['title' => 'Edited'],
        generationType: 'title',
        timeToDecisionMs: 2000,
    ));

    expect($output->action)->toBe('edited')
        ->and($output->editedOutput)->toBe(['title' => 'Edited'])
        ->and($output->timeToDecisionMs)->toBe(2000);
});

it('throws when edited feedback has no editedOutput', function () {
    $this->useCase->execute(new RecordGenerationFeedbackInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        generationId: (string) Uuid::generate(),
        action: 'edited',
        originalOutput: ['title' => 'Original'],
        generationType: 'title',
    ));
})->throws(\App\Domain\ContentAI\Exceptions\InvalidFeedbackException::class);
