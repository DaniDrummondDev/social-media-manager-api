<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\AdminUserOutput;
use App\Application\PlatformAdmin\DTOs\ListUsersAdminInput;

final class ListUsersAdminUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    /**
     * @return array{items: array<AdminUserOutput>, next_cursor: ?string, has_more: bool}
     */
    public function execute(ListUsersAdminInput $input): array
    {
        $filters = array_filter([
            'search' => $input->search,
            'status' => $input->status,
            'email_verified' => $input->emailVerified,
            'two_factor' => $input->twoFactor,
            'from' => $input->from,
            'to' => $input->to,
            'sort' => $input->sort,
        ], fn ($v) => $v !== null);

        $result = $this->queryService->listUsers(
            $filters,
            $input->perPage,
            $input->cursor,
        );

        $items = array_map(
            fn (array $data) => new AdminUserOutput(
                id: $data['id'],
                name: $data['name'],
                email: $data['email'],
                status: $data['status'],
                emailVerified: (bool) ($data['email_verified'] ?? false),
                twoFactorEnabled: (bool) ($data['two_factor_enabled'] ?? false),
                organizationsCount: (int) ($data['organizations_count'] ?? 0),
                lastLoginAt: $data['last_login_at'] ?? null,
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
