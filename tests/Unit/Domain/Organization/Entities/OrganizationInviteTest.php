<?php

declare(strict_types=1);

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Organization\Events\MemberInvited;
use App\Domain\Organization\Exceptions\InviteAlreadyAcceptedException;
use App\Domain\Organization\Exceptions\InviteExpiredException;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestInvite(int $expirationDays = 7): OrganizationInvite
{
    return OrganizationInvite::create(
        organizationId: Uuid::generate(),
        email: Email::fromString('invited@example.com'),
        role: OrganizationRole::Member,
        invitedBy: Uuid::generate(),
        expirationDays: $expirationDays,
    );
}

it('creates an invite with MemberInvited event', function () {
    $invite = createTestInvite();

    expect((string) $invite->email)->toBe('invited@example.com')
        ->and($invite->role)->toBe(OrganizationRole::Member)
        ->and($invite->acceptedAt)->toBeNull()
        ->and($invite->token)->toHaveLength(64)
        ->and($invite->domainEvents)->toHaveCount(1)
        ->and($invite->domainEvents[0])->toBeInstanceOf(MemberInvited::class)
        ->and($invite->domainEvents[0]->email)->toBe('invited@example.com');
});

it('creates an invite with custom expiration', function () {
    $invite = createTestInvite(expirationDays: 14);

    $expectedExpiry = (new DateTimeImmutable)->modify('+14 days');
    $diff = abs($invite->expiresAt->getTimestamp() - $expectedExpiry->getTimestamp());

    expect($diff)->toBeLessThan(2);
});

it('reconstitutes an invite without events', function () {
    $invite = createTestInvite();

    $reconstituted = OrganizationInvite::reconstitute(
        id: $invite->id,
        organizationId: $invite->organizationId,
        email: $invite->email,
        token: $invite->token,
        role: $invite->role,
        invitedBy: $invite->invitedBy,
        acceptedAt: $invite->acceptedAt,
        expiresAt: $invite->expiresAt,
        createdAt: $invite->createdAt,
    );

    expect($reconstituted->domainEvents)->toBeEmpty()
        ->and((string) $reconstituted->email)->toBe('invited@example.com');
});

it('accepts a pending invite', function () {
    $invite = createTestInvite();
    $accepted = $invite->accept();

    expect($accepted->acceptedAt)->not->toBeNull();
});

it('throws when accepting already accepted invite', function () {
    $invite = createTestInvite()->accept();
    $invite->accept();
})->throws(InviteAlreadyAcceptedException::class);

it('throws when accepting expired invite', function () {
    $invite = OrganizationInvite::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        email: Email::fromString('expired@example.com'),
        token: bin2hex(random_bytes(32)),
        role: OrganizationRole::Member,
        invitedBy: Uuid::generate(),
        acceptedAt: null,
        expiresAt: new DateTimeImmutable('-1 day'),
        createdAt: new DateTimeImmutable('-8 days'),
    );

    $invite->accept();
})->throws(InviteExpiredException::class);

it('identifies expired invite', function () {
    $expired = OrganizationInvite::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        email: Email::fromString('test@example.com'),
        token: 'token',
        role: OrganizationRole::Member,
        invitedBy: Uuid::generate(),
        acceptedAt: null,
        expiresAt: new DateTimeImmutable('-1 day'),
        createdAt: new DateTimeImmutable('-8 days'),
    );

    expect($expired->isExpired())->toBeTrue()
        ->and($expired->isPending())->toBeFalse();
});

it('identifies pending invite', function () {
    $invite = createTestInvite();

    expect($invite->isPending())->toBeTrue()
        ->and($invite->isExpired())->toBeFalse();
});

it('identifies accepted invite as not pending', function () {
    $accepted = createTestInvite()->accept();

    expect($accepted->isPending())->toBeFalse();
});

it('releases domain events', function () {
    $invite = createTestInvite();
    expect($invite->domainEvents)->toHaveCount(1);

    $released = $invite->releaseEvents();
    expect($released->domainEvents)->toBeEmpty();
});
