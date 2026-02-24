<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Organization\DTOs\OrganizationListOutput;
use App\Application\Organization\DTOs\OrganizationOutput;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListOrganizationsUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function execute(string $userId): OrganizationListOutput
    {
        $organizations = $this->organizationRepository->listByUserId(Uuid::fromString($userId));

        $outputs = array_map(
            fn ($org) => OrganizationOutput::fromEntity($org),
            $organizations,
        );

        return new OrganizationListOutput($outputs);
    }
}
