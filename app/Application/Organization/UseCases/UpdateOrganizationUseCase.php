<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Organization\DTOs\OrganizationOutput;
use App\Application\Organization\DTOs\UpdateOrganizationInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateOrganizationUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly OrganizationMemberRepositoryInterface $memberRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateOrganizationInput $input): OrganizationOutput
    {
        $orgId = Uuid::fromString($input->organizationId);
        $userId = Uuid::fromString($input->userId);

        $member = $this->memberRepository->findByOrgAndUser($orgId, $userId);

        if ($member === null || ! $member->role->canManageMembers()) {
            throw new AuthorizationException;
        }

        $organization = $this->organizationRepository->findById($orgId);

        if ($organization === null) {
            throw new DomainException('Organization not found', 'ORGANIZATION_NOT_FOUND');
        }

        $slug = $input->slug !== null ? OrganizationSlug::fromString($input->slug) : null;

        $updated = $organization->update(
            name: $input->name,
            slug: $slug,
            timezone: $input->timezone,
            userId: $input->userId,
        );

        if ($updated !== $organization) {
            $this->organizationRepository->update($updated);
            $this->eventDispatcher->dispatch(...$updated->domainEvents);
        }

        return OrganizationOutput::fromEntity($updated);
    }
}
