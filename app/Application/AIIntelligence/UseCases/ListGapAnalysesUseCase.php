<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GapAnalysisListOutput;
use App\Application\AIIntelligence\DTOs\ListGapAnalysesInput;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListGapAnalysesUseCase
{
    public function __construct(
        private readonly ContentGapAnalysisRepositoryInterface $gapAnalysisRepository,
    ) {}

    /**
     * @return array{items: array<GapAnalysisListOutput>, next_cursor: ?string}
     */
    public function execute(ListGapAnalysesInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->gapAnalysisRepository->findByOrganization(
            organizationId: $organizationId,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($analysis) => GapAnalysisListOutput::fromEntity($analysis),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
