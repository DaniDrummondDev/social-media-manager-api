<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\Shared\ValueObjects\Uuid;

interface PerformancePredictionRepositoryInterface
{
    public function create(PerformancePrediction $prediction): void;

    public function findById(Uuid $id): ?PerformancePrediction;

    /**
     * @return array<PerformancePrediction>
     */
    public function findByContentId(Uuid $contentId): array;

    /**
     * @return array{items: array<PerformancePrediction>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;
}
