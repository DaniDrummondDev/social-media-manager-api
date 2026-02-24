<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Organization\DTOs\RemoveMemberInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Organization\Events\MemberRemoved;
use App\Domain\Organization\Exceptions\CannotRemoveLastOwnerException;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

final class RemoveMemberUseCase
{
    public function __construct(
        private readonly OrganizationMemberRepositoryInterface $memberRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(RemoveMemberInput $input): void
    {
        $orgId = Uuid::fromString($input->organizationId);
        $userId = Uuid::fromString($input->userId);
        $targetUserId = Uuid::fromString($input->targetUserId);

        $requestingMember = $this->memberRepository->findByOrgAndUser($orgId, $userId);

        if ($requestingMember === null || ! $requestingMember->role->canManageMembers()) {
            throw new AuthorizationException;
        }

        $targetMember = $this->memberRepository->findByOrgAndUser($orgId, $targetUserId);

        if ($targetMember === null) {
            throw new DomainException('Member not found', 'MEMBER_NOT_FOUND');
        }

        if ($targetMember->isOwner()) {
            $members = $this->memberRepository->listByOrganization($orgId);
            $ownerCount = count(array_filter($members, fn ($m) => $m->role === OrganizationRole::Owner));

            if ($ownerCount <= 1) {
                throw new CannotRemoveLastOwnerException;
            }
        }

        $this->memberRepository->delete($orgId, $targetUserId);

        $this->eventDispatcher->dispatch(
            new MemberRemoved(
                aggregateId: (string) $targetMember->id,
                organizationId: $input->organizationId,
                userId: $input->targetUserId,
            ),
        );
    }
}
