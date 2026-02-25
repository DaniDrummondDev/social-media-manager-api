<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\Entities\ReportExport;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface ReportExportRepositoryInterface
{
    public function create(ReportExport $export): void;

    public function update(ReportExport $export): void;

    public function findById(Uuid $id): ?ReportExport;

    /**
     * @return array<ReportExport>
     */
    public function findByOrganizationId(Uuid $organizationId): array;

    public function countRecentByUser(Uuid $userId, DateTimeImmutable $since): int;

    /**
     * @return array<ReportExport>
     */
    public function findExpired(DateTimeImmutable $now): array;
}
