<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Contracts;

use App\Domain\ContentAI\Entities\GenerationFeedback;
use App\Domain\Shared\ValueObjects\Uuid;

interface GenerationFeedbackRepositoryInterface
{
    public function create(GenerationFeedback $feedback): void;

    public function update(GenerationFeedback $feedback): void;

    public function findById(Uuid $id): ?GenerationFeedback;

    public function findByGenerationId(Uuid $generationId): ?GenerationFeedback;

    /**
     * @return array<GenerationFeedback>
     */
    public function findEditedByOrganizationAndType(
        Uuid $organizationId,
        string $generationType,
        int $limit = 100,
    ): array;

    /**
     * @return array{accepted: int, edited: int, rejected: int, total: int}
     */
    public function countByOrganizationAndType(
        Uuid $organizationId,
        string $generationType,
    ): array;
}
