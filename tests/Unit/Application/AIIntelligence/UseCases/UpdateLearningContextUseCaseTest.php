<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\UpdateLearningContextInput;
use App\Application\AIIntelligence\UseCases\UpdateLearningContextUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Events\LearningContextUpdated;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new UpdateLearningContextUseCase(
        $this->eventDispatcher,
    );
});

it('dispatches LearningContextUpdated event', function () {
    $orgId = (string) Uuid::generate();

    $this->eventDispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::on(fn ($event) => $event instanceof LearningContextUpdated
            && $event->organizationId === $orgId
            && $event->contextTypesUpdated === ['rag_examples', 'org_style']
        ));

    $this->useCase->execute(new UpdateLearningContextInput(
        organizationId: $orgId,
        userId: 'user-1',
        contextTypes: ['rag_examples', 'org_style'],
    ));
});

it('dispatches with single context type', function () {
    $this->eventDispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::on(fn ($event) => $event instanceof LearningContextUpdated
            && $event->contextTypesUpdated === ['audience_context']
        ));

    $this->useCase->execute(new UpdateLearningContextInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        contextTypes: ['audience_context'],
    ));
});
