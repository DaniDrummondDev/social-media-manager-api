<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\UpdateLearningContextInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Events\LearningContextUpdated;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateLearningContextUseCase
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Refresh ai_generation_context combining RAG examples, org_style, and audience context.
     *
     * Actual context aggregation is handled by infrastructure implementations
     * triggered via the LearningContextUpdated event.
     */
    public function execute(UpdateLearningContextInput $input): void
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $this->eventDispatcher->dispatch(
            new LearningContextUpdated(
                aggregateId: (string) $organizationId,
                organizationId: $input->organizationId,
                userId: $input->userId,
                contextTypesUpdated: $input->contextTypes,
            ),
        );
    }
}
