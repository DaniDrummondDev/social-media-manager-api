<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CrmConnectionOutput;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListCrmConnectionsUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
    ) {}

    /**
     * @return array<CrmConnectionOutput>
     */
    public function execute(string $organizationId): array
    {
        $orgId = Uuid::fromString($organizationId);
        $connections = $this->connectionRepository->findByOrganizationId($orgId);

        return array_map(
            fn ($connection) => CrmConnectionOutput::fromEntity($connection),
            $connections,
        );
    }
}
