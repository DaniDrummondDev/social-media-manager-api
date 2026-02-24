<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\DTOs\AIGenerationOutput;
use App\Application\ContentAI\DTOs\AIHistoryListOutput;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListAIHistoryUseCase
{
    public function __construct(
        private readonly AIGenerationRepositoryInterface $generationRepository,
    ) {}

    public function execute(string $organizationId, ?string $type = null): AIHistoryListOutput
    {
        $generations = $this->generationRepository->findByOrganizationId(
            Uuid::fromString($organizationId),
            $type,
        );

        $outputs = array_map(
            fn ($gen) => AIGenerationOutput::fromEntity($gen),
            $generations,
        );

        return new AIHistoryListOutput(items: $outputs);
    }
}
