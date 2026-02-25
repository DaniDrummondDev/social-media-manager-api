<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Repositories;

use App\Domain\PlatformAdmin\Entities\AdminAuditEntry;

interface AdminAuditEntryRepositoryInterface
{
    public function create(AdminAuditEntry $entry): void;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<AdminAuditEntry>, next_cursor: ?string, has_more: bool}
     */
    public function findByFilters(array $filters, int $perPage, ?string $cursor): array;
}
