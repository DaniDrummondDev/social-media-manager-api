<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;
use App\Domain\Shared\ValueObjects\Uuid;

interface ContentGapAnalysisRepositoryInterface
{
    public function create(ContentGapAnalysis $analysis): void;

    public function update(ContentGapAnalysis $analysis): void;

    public function findById(Uuid $id): ?ContentGapAnalysis;

    /**
     * @return array{items: array<ContentGapAnalysis>, next_cursor: ?string}
     */
    public function findByOrganization(
        Uuid $organizationId,
        ?string $cursor = null,
        int $limit = 20,
    ): array;
}
