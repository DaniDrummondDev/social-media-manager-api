<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\AuditEntryOutput;
use App\Application\PlatformAdmin\DTOs\ListAuditLogInput;
use App\Domain\PlatformAdmin\Repositories\AdminAuditEntryRepositoryInterface;

final class ListAuditLogUseCase
{
    public function __construct(
        private readonly AdminAuditEntryRepositoryInterface $auditRepository,
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    /**
     * @return array{items: array<AuditEntryOutput>, next_cursor: ?string, has_more: bool}
     */
    public function execute(ListAuditLogInput $input): array
    {
        $filters = array_filter([
            'admin_id' => $input->adminId,
            'action' => $input->action,
            'resource_type' => $input->resourceType,
            'resource_id' => $input->resourceId,
            'from' => $input->from,
            'to' => $input->to,
        ], fn ($v) => $v !== null);

        $result = $this->auditRepository->findByFilters(
            $filters,
            $input->perPage,
            $input->cursor,
        );

        // Collect unique admin IDs to resolve names in a single pass
        $adminIds = array_unique(
            array_map(fn ($entry) => (string) $entry->adminId, $result['items']),
        );

        $adminInfoMap = [];
        foreach ($adminIds as $id) {
            $adminInfoMap[$id] = $this->queryService->getAdminInfo($id);
        }

        $items = array_map(
            fn ($entry) => AuditEntryOutput::fromEntity(
                $entry,
                $adminInfoMap[(string) $entry->adminId] ?? null,
            ),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
            'has_more' => $result['has_more'],
        ];
    }
}
