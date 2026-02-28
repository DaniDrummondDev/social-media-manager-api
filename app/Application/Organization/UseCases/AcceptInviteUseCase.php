<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\Exceptions\InvalidTokenException;
use App\Application\Organization\DTOs\AcceptInviteInput;
use App\Application\Organization\DTOs\OrganizationMemberOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Exceptions\MemberAlreadyExistsException;
use App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class AcceptInviteUseCase
{
    public function __construct(
        private readonly OrganizationInviteRepositoryInterface $inviteRepository,
        private readonly OrganizationMemberRepositoryInterface $memberRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(AcceptInviteInput $input): OrganizationMemberOutput
    {
        $invite = $this->inviteRepository->findByToken($input->token);

        if ($invite === null) {
            throw new InvalidTokenException('invite');
        }

        $userId = Uuid::fromString($input->userId);

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        $accepted = $invite->accept();

        $member = OrganizationMember::create(
            organizationId: $invite->organizationId,
            userId: $userId,
            role: $invite->role,
            invitedBy: $invite->invitedBy,
        );

        $this->inviteRepository->update($accepted);

        $created = $this->memberRepository->createIfNotExists($member);

        if (! $created) {
            throw new MemberAlreadyExistsException;
        }

        $this->eventDispatcher->dispatch(...$member->domainEvents);

        return OrganizationMemberOutput::fromEntity($member);
    }
}
