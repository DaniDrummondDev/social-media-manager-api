<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\PredictionValidation;
use App\Domain\Shared\ValueObjects\Uuid;

interface PredictionValidationRepositoryInterface
{
    public function create(PredictionValidation $validation): void;

    public function findById(Uuid $id): ?PredictionValidation;

    public function findByPredictionId(Uuid $predictionId): ?PredictionValidation;

    /**
     * @return array<PredictionValidation>
     */
    public function findByOrganization(
        Uuid $organizationId,
        int $limit = 50,
    ): array;

    /**
     * Count validations for an organization (min 10 required to show metrics).
     */
    public function countByOrganization(Uuid $organizationId): int;

    /**
     * @return array{mae: float, count: int}|null
     */
    public function calculateAccuracyMetrics(Uuid $organizationId): ?array;
}
