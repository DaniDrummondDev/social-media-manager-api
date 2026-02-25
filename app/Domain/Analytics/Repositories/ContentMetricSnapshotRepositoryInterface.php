<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\Entities\ContentMetricSnapshot;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface ContentMetricSnapshotRepositoryInterface
{
    public function create(ContentMetricSnapshot $snapshot): void;

    /**
     * @return array<ContentMetricSnapshot>
     */
    public function findByMetricsId(Uuid $contentMetricId): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEvolution(Uuid $contentMetricId, DateTimeImmutable $publishedAt): array;
}
