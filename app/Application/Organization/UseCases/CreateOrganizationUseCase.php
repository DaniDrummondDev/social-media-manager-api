<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Organization\DTOs\CreateOrganizationInput;
use App\Application\Organization\DTOs\OrganizationOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateOrganizationUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly OrganizationMemberRepositoryInterface $memberRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateOrganizationInput $input): OrganizationOutput
    {
        $ownerId = Uuid::fromString($input->userId);
        $slug = OrganizationSlug::fromString($input->slug);

        $organization = Organization::create(
            name: $input->name,
            slug: $slug,
            ownerId: $ownerId,
            timezone: $input->timezone,
        );

        $this->organizationRepository->create($organization);

        $member = OrganizationMember::create(
            organizationId: $organization->id,
            userId: $ownerId,
            role: OrganizationRole::Owner,
        );

        $this->memberRepository->create($member);

        $this->eventDispatcher->dispatch(
            ...$organization->domainEvents,
            ...$member->domainEvents,
        );

        return OrganizationOutput::fromEntity($organization);
    }
}
