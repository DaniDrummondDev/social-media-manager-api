<?php

declare(strict_types=1);

use App\Domain\Organization\ValueObjects\OrganizationStatus;

it('allows active organizations to operate', function () {
    expect(OrganizationStatus::Active->canOperate())->toBeTrue();
});

it('prevents suspended organizations from operating', function () {
    expect(OrganizationStatus::Suspended->canOperate())->toBeFalse();
});

it('prevents deleted organizations from operating', function () {
    expect(OrganizationStatus::Deleted->canOperate())->toBeFalse();
});

it('allows active to transition to suspended', function () {
    expect(OrganizationStatus::Active->canTransitionTo(OrganizationStatus::Suspended))->toBeTrue();
});

it('allows active to transition to deleted', function () {
    expect(OrganizationStatus::Active->canTransitionTo(OrganizationStatus::Deleted))->toBeTrue();
});

it('allows suspended to transition to active', function () {
    expect(OrganizationStatus::Suspended->canTransitionTo(OrganizationStatus::Active))->toBeTrue();
});

it('allows suspended to transition to deleted', function () {
    expect(OrganizationStatus::Suspended->canTransitionTo(OrganizationStatus::Deleted))->toBeTrue();
});

it('prevents deleted from any transition', function () {
    expect(OrganizationStatus::Deleted->canTransitionTo(OrganizationStatus::Active))->toBeFalse()
        ->and(OrganizationStatus::Deleted->canTransitionTo(OrganizationStatus::Suspended))->toBeFalse();
});
