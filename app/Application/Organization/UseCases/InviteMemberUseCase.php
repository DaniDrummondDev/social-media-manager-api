<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Organization\DTOs\InviteMemberInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Organization\Exceptions\MemberAlreadyExistsException;
use App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;

final class InviteMemberUseCase
{
    public function __construct(
        private readonly OrganizationMemberRepositoryInterface $memberRepository,
        private readonly OrganizationInviteRepositoryInterface $inviteRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(InviteMemberInput $input): MessageOutput
    {
        $orgId = Uuid::fromString($input->organizationId);
        $userId = Uuid::fromString($input->userId);

        $member = $this->memberRepository->findByOrgAndUser($orgId, $userId);

        if ($member === null || ! $member->role->canManageMembers()) {
            throw new AuthorizationException;
        }

        $email = Email::fromString($input->email);

        $existingInvite = $this->inviteRepository->findPendingByOrgAndEmail($orgId, $email);

        if ($existingInvite !== null) {
            throw new MemberAlreadyExistsException;
        }

        $invite = OrganizationInvite::create(
            organizationId: $orgId,
            email: $email,
            role: OrganizationRole::from($input->role),
            invitedBy: $userId,
        );

        $this->inviteRepository->create($invite);
        $this->eventDispatcher->dispatch(...$invite->domainEvents);

        return new MessageOutput('Invitation sent to '.$input->email);
    }
}
