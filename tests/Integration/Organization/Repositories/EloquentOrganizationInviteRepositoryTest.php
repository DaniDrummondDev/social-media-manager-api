<?php

declare(strict_types=1);

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->repository = app(OrganizationInviteRepositoryInterface::class);
    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = Uuid::fromString($this->orgData['org']['id']);
    $this->inviterId = Uuid::fromString($this->user['id']);
});

it('creates an invite and finds by token', function () {
    $invite = OrganizationInvite::create(
        organizationId: $this->orgId,
        email: Email::fromString('invited@example.com'),
        role: OrganizationRole::Member,
        invitedBy: $this->inviterId,
    );

    $this->repository->create($invite);

    $found = $this->repository->findByToken($invite->token);

    expect($found)->not->toBeNull()
        ->and((string) $found->email)->toBe('invited@example.com')
        ->and($found->role)->toBe(OrganizationRole::Member)
        ->and($found->acceptedAt)->toBeNull();
});

it('returns null for non-existent token', function () {
    expect($this->repository->findByToken('nonexistent'))->toBeNull();
});

it('finds pending invite by org and email', function () {
    $invite = OrganizationInvite::create(
        organizationId: $this->orgId,
        email: Email::fromString('pending@example.com'),
        role: OrganizationRole::Admin,
        invitedBy: $this->inviterId,
    );

    $this->repository->create($invite);

    $found = $this->repository->findPendingByOrgAndEmail(
        $this->orgId,
        Email::fromString('pending@example.com'),
    );

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $invite->id);
});

it('marks invite as accepted via update', function () {
    $invite = OrganizationInvite::create(
        organizationId: $this->orgId,
        email: Email::fromString('accept@example.com'),
        role: OrganizationRole::Member,
        invitedBy: $this->inviterId,
    );

    $this->repository->create($invite);

    $accepted = $invite->accept();
    $this->repository->update($accepted);

    $found = $this->repository->findByToken($invite->token);

    expect($found->acceptedAt)->not->toBeNull();
});

it('deletes an invite', function () {
    $invite = OrganizationInvite::create(
        organizationId: $this->orgId,
        email: Email::fromString('delete@example.com'),
        role: OrganizationRole::Member,
        invitedBy: $this->inviterId,
    );

    $this->repository->create($invite);
    $this->repository->delete($invite->id);

    expect($this->repository->findByToken($invite->token))->toBeNull();
});

it('deletes expired invites', function () {
    // Create an expired invite via reconstitute to set custom expiresAt
    $expiredInvite = OrganizationInvite::reconstitute(
        id: Uuid::generate(),
        organizationId: $this->orgId,
        email: Email::fromString('expired@example.com'),
        token: bin2hex(random_bytes(32)),
        role: OrganizationRole::Member,
        invitedBy: $this->inviterId,
        acceptedAt: null,
        expiresAt: new \DateTimeImmutable('-1 day'),
        createdAt: new \DateTimeImmutable('-8 days'),
    );

    $validInvite = OrganizationInvite::create(
        organizationId: $this->orgId,
        email: Email::fromString('valid@example.com'),
        role: OrganizationRole::Member,
        invitedBy: $this->inviterId,
    );

    $this->repository->create($expiredInvite);
    $this->repository->create($validInvite);

    $deleted = $this->repository->deleteExpired();

    expect($deleted)->toBe(1)
        ->and($this->repository->findByToken($expiredInvite->token))->toBeNull()
        ->and($this->repository->findByToken($validInvite->token))->not->toBeNull();
});

it('does not find accepted invite as pending', function () {
    $invite = OrganizationInvite::create(
        organizationId: $this->orgId,
        email: Email::fromString('accepted@example.com'),
        role: OrganizationRole::Member,
        invitedBy: $this->inviterId,
    );

    $this->repository->create($invite);

    $accepted = $invite->accept();
    $this->repository->update($accepted);

    $found = $this->repository->findPendingByOrgAndEmail(
        $this->orgId,
        Email::fromString('accepted@example.com'),
    );

    expect($found)->toBeNull();
});
