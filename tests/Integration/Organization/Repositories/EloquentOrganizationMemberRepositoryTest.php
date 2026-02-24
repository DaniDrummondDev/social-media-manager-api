<?php

declare(strict_types=1);

use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->repository = app(OrganizationMemberRepositoryInterface::class);
    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = Uuid::fromString($this->orgData['org']['id']);
    $this->userId = Uuid::fromString($this->user['id']);
});

it('creates a member and finds by org and user', function () {
    $secondUser = $this->createUserInDb(['email' => 'second@example.com']);
    $secondUserId = Uuid::fromString($secondUser['id']);

    $member = OrganizationMember::create(
        organizationId: $this->orgId,
        userId: $secondUserId,
        role: OrganizationRole::Member,
        invitedBy: $this->userId,
    );

    $this->repository->create($member);

    $found = $this->repository->findByOrgAndUser($this->orgId, $secondUserId);

    expect($found)->not->toBeNull()
        ->and($found->role)->toBe(OrganizationRole::Member)
        ->and((string) $found->invitedBy)->toBe((string) $this->userId);
});

it('returns null when member not found', function () {
    $found = $this->repository->findByOrgAndUser($this->orgId, Uuid::generate());

    expect($found)->toBeNull();
});

it('lists members by organization', function () {
    $list = $this->repository->listByOrganization($this->orgId);

    // Owner created in beforeEach
    expect($list)->toHaveCount(1)
        ->and($list[0]->role)->toBe(OrganizationRole::Owner);
});

it('updates member role', function () {
    $found = $this->repository->findByOrgAndUser($this->orgId, $this->userId);
    $changed = $found->changeRole(OrganizationRole::Admin);
    $this->repository->update($changed);

    $reloaded = $this->repository->findByOrgAndUser($this->orgId, $this->userId);

    expect($reloaded->role)->toBe(OrganizationRole::Admin);
});

it('deletes a member', function () {
    $secondUser = $this->createUserInDb(['email' => 'todelete@example.com']);
    $secondUserId = Uuid::fromString($secondUser['id']);

    $member = OrganizationMember::create(
        organizationId: $this->orgId,
        userId: $secondUserId,
        role: OrganizationRole::Member,
    );

    $this->repository->create($member);
    $this->repository->delete($this->orgId, $secondUserId);

    expect($this->repository->findByOrgAndUser($this->orgId, $secondUserId))->toBeNull();
});

it('counts members by organization', function () {
    $secondUser = $this->createUserInDb(['email' => 'count@example.com']);

    $member = OrganizationMember::create(
        organizationId: $this->orgId,
        userId: Uuid::fromString($secondUser['id']),
        role: OrganizationRole::Member,
    );

    $this->repository->create($member);

    expect($this->repository->countByOrganization($this->orgId))->toBe(2);
});
