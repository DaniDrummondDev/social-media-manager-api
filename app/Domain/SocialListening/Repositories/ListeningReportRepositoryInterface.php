<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningReport;

interface ListeningReportRepositoryInterface
{
    public function create(ListeningReport $report): void;

    public function update(ListeningReport $report): void;

    public function findById(Uuid $id): ?ListeningReport;

    /**
     * @return array{items: array<ListeningReport>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;
}
