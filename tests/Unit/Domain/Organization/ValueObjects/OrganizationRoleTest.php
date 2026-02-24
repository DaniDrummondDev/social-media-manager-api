<?php

declare(strict_types=1);

use App\Domain\Organization\ValueObjects\OrganizationRole;

it('allows owner to manage members', function () {
    expect(OrganizationRole::Owner->canManageMembers())->toBeTrue();
});

it('allows admin to manage members', function () {
    expect(OrganizationRole::Admin->canManageMembers())->toBeTrue();
});

it('prevents member from managing members', function () {
    expect(OrganizationRole::Member->canManageMembers())->toBeFalse();
});

it('allows only owner to manage billing', function () {
    expect(OrganizationRole::Owner->canManageBilling())->toBeTrue()
        ->and(OrganizationRole::Admin->canManageBilling())->toBeFalse()
        ->and(OrganizationRole::Member->canManageBilling())->toBeFalse();
});

it('allows only owner to delete organization', function () {
    expect(OrganizationRole::Owner->canDeleteOrganization())->toBeTrue()
        ->and(OrganizationRole::Admin->canDeleteOrganization())->toBeFalse()
        ->and(OrganizationRole::Member->canDeleteOrganization())->toBeFalse();
});

it('allows all roles to manage content', function () {
    expect(OrganizationRole::Owner->canManageContent())->toBeTrue()
        ->and(OrganizationRole::Admin->canManageContent())->toBeTrue()
        ->and(OrganizationRole::Member->canManageContent())->toBeTrue();
});

it('checks role hierarchy with isAtLeast', function () {
    expect(OrganizationRole::Owner->isAtLeast(OrganizationRole::Owner))->toBeTrue()
        ->and(OrganizationRole::Owner->isAtLeast(OrganizationRole::Admin))->toBeTrue()
        ->and(OrganizationRole::Owner->isAtLeast(OrganizationRole::Member))->toBeTrue()
        ->and(OrganizationRole::Admin->isAtLeast(OrganizationRole::Owner))->toBeFalse()
        ->and(OrganizationRole::Admin->isAtLeast(OrganizationRole::Admin))->toBeTrue()
        ->and(OrganizationRole::Admin->isAtLeast(OrganizationRole::Member))->toBeTrue()
        ->and(OrganizationRole::Member->isAtLeast(OrganizationRole::Owner))->toBeFalse()
        ->and(OrganizationRole::Member->isAtLeast(OrganizationRole::Admin))->toBeFalse()
        ->and(OrganizationRole::Member->isAtLeast(OrganizationRole::Member))->toBeTrue();
});
