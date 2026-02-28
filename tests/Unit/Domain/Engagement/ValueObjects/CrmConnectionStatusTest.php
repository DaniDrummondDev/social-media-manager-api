<?php

declare(strict_types=1);

use App\Domain\Engagement\ValueObjects\CrmConnectionStatus;

it('reports isActive correctly', function () {
    expect(CrmConnectionStatus::Connected->isActive())->toBeTrue()
        ->and(CrmConnectionStatus::TokenExpired->isActive())->toBeFalse()
        ->and(CrmConnectionStatus::Revoked->isActive())->toBeFalse()
        ->and(CrmConnectionStatus::Error->isActive())->toBeFalse();
});

it('reports canSync correctly', function () {
    expect(CrmConnectionStatus::Connected->canSync())->toBeTrue()
        ->and(CrmConnectionStatus::TokenExpired->canSync())->toBeFalse()
        ->and(CrmConnectionStatus::Revoked->canSync())->toBeFalse()
        ->and(CrmConnectionStatus::Error->canSync())->toBeFalse();
});

it('allows valid transitions from connected', function () {
    $connected = CrmConnectionStatus::Connected;

    expect($connected->canTransitionTo(CrmConnectionStatus::TokenExpired))->toBeTrue()
        ->and($connected->canTransitionTo(CrmConnectionStatus::Revoked))->toBeTrue()
        ->and($connected->canTransitionTo(CrmConnectionStatus::Error))->toBeTrue()
        ->and($connected->canTransitionTo(CrmConnectionStatus::Connected))->toBeTrue();
});

it('allows valid transitions from token_expired', function () {
    $expired = CrmConnectionStatus::TokenExpired;

    expect($expired->canTransitionTo(CrmConnectionStatus::Connected))->toBeTrue()
        ->and($expired->canTransitionTo(CrmConnectionStatus::Revoked))->toBeTrue()
        ->and($expired->canTransitionTo(CrmConnectionStatus::Error))->toBeTrue()
        ->and($expired->canTransitionTo(CrmConnectionStatus::TokenExpired))->toBeFalse();
});

it('allows valid transitions from error', function () {
    $error = CrmConnectionStatus::Error;

    expect($error->canTransitionTo(CrmConnectionStatus::Connected))->toBeTrue()
        ->and($error->canTransitionTo(CrmConnectionStatus::Revoked))->toBeTrue()
        ->and($error->canTransitionTo(CrmConnectionStatus::TokenExpired))->toBeFalse()
        ->and($error->canTransitionTo(CrmConnectionStatus::Error))->toBeFalse();
});

it('blocks all transitions from revoked', function () {
    $revoked = CrmConnectionStatus::Revoked;

    expect($revoked->canTransitionTo(CrmConnectionStatus::Connected))->toBeFalse()
        ->and($revoked->canTransitionTo(CrmConnectionStatus::TokenExpired))->toBeFalse()
        ->and($revoked->canTransitionTo(CrmConnectionStatus::Error))->toBeFalse()
        ->and($revoked->canTransitionTo(CrmConnectionStatus::Revoked))->toBeFalse();
});
