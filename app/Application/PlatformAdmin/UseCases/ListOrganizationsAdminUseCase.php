<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\AdminOrganizationOutput;
use App\Application\PlatformAdmin\DTOs\ListOrganizationsAdminInput;

final class ListOrganizationsAdminUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    /**
     * @return array{items: array<AdminOrganizationOutput>, next_cursor: ?string, has_more: bool}
     */
    public function execute(ListOrganizationsAdminInput $input): array
    {
        $filters = array_filter([
            'search' => $input->search,
            'status' => $input->status,
            'plan_slug' => $input->plan,
            'from' => $input->from,
            'to' => $input->to,
            'sort' => $input->sort,
        ], fn ($v) => $v !== null);

        $result = $this->queryService->listOrganizations(
            $filters,
            $input->perPage,
            $input->cursor,
        );

        $items = array_map(
            fn (array $data) => new AdminOrganizationOutput(
                id: $data['id'],
                name: $data['name'],
                status: $data['status'],
                plan: $data['plan'] ?? null,
                membersCount: (int) ($data['members_count'] ?? 0),
                socialAccountsCount: (int) ($data['social_accounts_count'] ?? 0),
                owner: $data['owner'] ?? null,
                subscriptionStatus: $data['subscription_status'] ?? null,
                createdAt: $data['created_at'],
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
