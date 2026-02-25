<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\AdminOrganizationDetailOutput;
use App\Application\PlatformAdmin\Exceptions\AdminOrganizationNotFoundException;

final class GetOrganizationDetailUseCase
{
    public function __construct(
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    public function execute(string $organizationId): AdminOrganizationDetailOutput
    {
        $data = $this->queryService->getOrganizationDetail($organizationId);

        if ($data === null) {
            throw new AdminOrganizationNotFoundException;
        }

        return new AdminOrganizationDetailOutput(
            id: $data['id'],
            name: $data['name'],
            status: $data['status'],
            createdAt: $data['created_at'],
            suspendedAt: $data['suspended_at'] ?? null,
            suspensionReason: $data['suspension_reason'] ?? null,
            members: $data['members'] ?? [],
            subscription: $data['subscription'] ?? null,
            usage: $data['usage'] ?? [],
            socialAccounts: $data['social_accounts'] ?? [],
        );
    }
}
