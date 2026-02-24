<?php

declare(strict_types=1);

use App\Domain\Identity\ValueObjects\UserStatus;

it('allows active users to login', function () {
    expect(UserStatus::Active->canLogin())->toBeTrue();
});

it('prevents inactive users from logging in', function () {
    expect(UserStatus::Inactive->canLogin())->toBeFalse();
});

it('prevents suspended users from logging in', function () {
    expect(UserStatus::Suspended->canLogin())->toBeFalse();
});

it('allows active to transition to inactive', function () {
    expect(UserStatus::Active->canTransitionTo(UserStatus::Inactive))->toBeTrue();
});

it('allows active to transition to suspended', function () {
    expect(UserStatus::Active->canTransitionTo(UserStatus::Suspended))->toBeTrue();
});

it('prevents active to transition to active', function () {
    expect(UserStatus::Active->canTransitionTo(UserStatus::Active))->toBeFalse();
});

it('allows inactive to transition to active', function () {
    expect(UserStatus::Inactive->canTransitionTo(UserStatus::Active))->toBeTrue();
});

it('prevents inactive to transition to suspended', function () {
    expect(UserStatus::Inactive->canTransitionTo(UserStatus::Suspended))->toBeFalse();
});

it('allows suspended to transition to active', function () {
    expect(UserStatus::Suspended->canTransitionTo(UserStatus::Active))->toBeTrue();
});

it('prevents suspended to transition to inactive', function () {
    expect(UserStatus::Suspended->canTransitionTo(UserStatus::Inactive))->toBeFalse();
});
