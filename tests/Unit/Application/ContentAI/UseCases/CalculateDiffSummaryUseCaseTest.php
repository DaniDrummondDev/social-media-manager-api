<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\CalculateDiffSummaryInput;
use App\Application\ContentAI\UseCases\CalculateDiffSummaryUseCase;
use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\ContentAI\Entities\GenerationFeedback;
use App\Domain\ContentAI\ValueObjects\FeedbackAction;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->feedbackRepository = Mockery::mock(GenerationFeedbackRepositoryInterface::class);

    $this->useCase = new CalculateDiffSummaryUseCase(
        $this->feedbackRepository,
    );
});

it('computes diff summary and updates feedback', function () {
    $feedbackId = Uuid::generate();

    $feedback = GenerationFeedback::reconstitute(
        id: $feedbackId,
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        generationId: Uuid::generate(),
        action: FeedbackAction::Edited,
        originalOutput: ['title' => 'Hello World'],
        editedOutput: ['title' => 'Hello Earth'],
        diffSummary: null,
        contentId: null,
        generationType: 'title',
        timeToDecisionMs: 1000,
        createdAt: new DateTimeImmutable,
    );

    $this->feedbackRepository->shouldReceive('findById')
        ->once()
        ->andReturn($feedback);

    $this->feedbackRepository->shouldReceive('update')
        ->once()
        ->with(Mockery::on(fn ($updated) => $updated->diffSummary !== null
            && $updated->diffSummary->changeRatio > 0.0
        ));

    $this->useCase->execute(new CalculateDiffSummaryInput(
        feedbackId: (string) $feedbackId,
    ));
});

it('does nothing when feedback not found', function () {
    $this->feedbackRepository->shouldReceive('findById')->once()->andReturn(null);
    $this->feedbackRepository->shouldNotReceive('update');

    $this->useCase->execute(new CalculateDiffSummaryInput(
        feedbackId: (string) Uuid::generate(),
    ));
});

it('does nothing when editedOutput is null', function () {
    $feedback = GenerationFeedback::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        generationId: Uuid::generate(),
        action: FeedbackAction::Accepted,
        originalOutput: ['title' => 'Test'],
        editedOutput: null,
        diffSummary: null,
        contentId: null,
        generationType: 'title',
        timeToDecisionMs: null,
        createdAt: new DateTimeImmutable,
    );

    $this->feedbackRepository->shouldReceive('findById')->once()->andReturn($feedback);
    $this->feedbackRepository->shouldNotReceive('update');

    $this->useCase->execute(new CalculateDiffSummaryInput(
        feedbackId: (string) $feedback->id,
    ));
});
