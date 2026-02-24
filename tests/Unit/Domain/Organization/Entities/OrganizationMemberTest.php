<?php

declare(strict_types=1);

use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Events\MemberAdded;
use App\Domain\Organization\Events\MemberRoleChanged;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates a member with MemberAdded event', function () {
    $orgId = Uuid::generate();
    $userId = Uuid::generate();

    $member = OrganizationMember::create(
        organizationId: $orgId,
        userId: $userId,
        role: OrganizationRole::Member,
    );

    expect((string) $member->organizationId)->toBe((string) $orgId)
        ->and((string) $member->userId)->toBe((string) $userId)
        ->and($member->role)->toBe(OrganizationRole::Member)
        ->and($member->invitedBy)->toBeNull()
        ->and($member->domainEvents)->toHaveCount(1)
        ->and($member->domainEvents[0])->toBeInstanceOf(MemberAdded::class)
        ->and($member->domainEvents[0]->role)->toBe('member');
});

it('creates a member with invitedBy', function () {
    $inviter = Uuid::generate();

    $member = OrganizationMember::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        role: OrganizationRole::Admin,
        invitedBy: $inviter,
    );

    expect((string) $member->invitedBy)->toBe((string) $inviter);
});

it('reconstitutes a member without events', function () {
    $member = OrganizationMember::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        role: OrganizationRole::Owner,
    );

    $reconstituted = OrganizationMember::reconstitute(
        id: $member->id,
        organizationId: $member->organizationId,
        userId: $member->userId,
        role: $member->role,
        invitedBy: $member->invitedBy,
        joinedAt: $member->joinedAt,
    );

    expect($reconstituted->domainEvents)->toBeEmpty()
        ->and($reconstituted->role)->toBe(OrganizationRole::Owner);
});

it('changes member role', function () {
    $member = OrganizationMember::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        role: OrganizationRole::Member,
    );

    $updated = $member->changeRole(OrganizationRole::Admin);

    expect($updated->role)->toBe(OrganizationRole::Admin)
        ->and($updated->domainEvents)->toHaveCount(2)
        ->and($updated->domainEvents[1])->toBeInstanceOf(MemberRoleChanged::class)
        ->and($updated->domainEvents[1]->oldRole)->toBe('member')
        ->and($updated->domainEvents[1]->newRole)->toBe('admin');
});

it('returns same instance when changing to same role', function () {
    $member = OrganizationMember::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        role: OrganizationRole::Admin,
    );

    $updated = $member->changeRole(OrganizationRole::Admin);

    expect($updated)->toBe($member);
});

it('identifies owner role', function () {
    $owner = OrganizationMember::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        role: OrganizationRole::Owner,
    );

    $member = OrganizationMember::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        role: OrganizationRole::Member,
    );

    expect($owner->isOwner())->toBeTrue()
        ->and($member->isOwner())->toBeFalse();
});

it('releases domain events', function () {
    $member = OrganizationMember::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        role: OrganizationRole::Member,
    );

    expect($member->domainEvents)->toHaveCount(1);

    $released = $member->releaseEvents();
    expect($released->domainEvents)->toBeEmpty();
});
