<?php

declare(strict_types=1);

use App\Domain\ClientFinance\ValueObjects\ContractStatus;

describe('Valid transitions', function () {
    it('allows active -> paused', function () {
        expect(ContractStatus::Active->canTransitionTo(ContractStatus::Paused))->toBeTrue();
    });

    it('allows active -> completed', function () {
        expect(ContractStatus::Active->canTransitionTo(ContractStatus::Completed))->toBeTrue();
    });

    it('allows active -> cancelled', function () {
        expect(ContractStatus::Active->canTransitionTo(ContractStatus::Cancelled))->toBeTrue();
    });

    it('allows paused -> active', function () {
        expect(ContractStatus::Paused->canTransitionTo(ContractStatus::Active))->toBeTrue();
    });

    it('allows paused -> completed', function () {
        expect(ContractStatus::Paused->canTransitionTo(ContractStatus::Completed))->toBeTrue();
    });

    it('allows paused -> cancelled', function () {
        expect(ContractStatus::Paused->canTransitionTo(ContractStatus::Cancelled))->toBeTrue();
    });
});

describe('Invalid transitions', function () {
    it('disallows completed -> any status', function () {
        expect(ContractStatus::Completed->canTransitionTo(ContractStatus::Active))->toBeFalse()
            ->and(ContractStatus::Completed->canTransitionTo(ContractStatus::Paused))->toBeFalse()
            ->and(ContractStatus::Completed->canTransitionTo(ContractStatus::Cancelled))->toBeFalse();
    });

    it('disallows cancelled -> any status', function () {
        expect(ContractStatus::Cancelled->canTransitionTo(ContractStatus::Active))->toBeFalse()
            ->and(ContractStatus::Cancelled->canTransitionTo(ContractStatus::Paused))->toBeFalse()
            ->and(ContractStatus::Cancelled->canTransitionTo(ContractStatus::Completed))->toBeFalse();
    });
});

describe('isActive', function () {
    it('returns true for active', function () {
        expect(ContractStatus::Active->isActive())->toBeTrue();
    });

    it('returns false for paused', function () {
        expect(ContractStatus::Paused->isActive())->toBeFalse();
    });

    it('returns false for completed', function () {
        expect(ContractStatus::Completed->isActive())->toBeFalse();
    });

    it('returns false for cancelled', function () {
        expect(ContractStatus::Cancelled->isActive())->toBeFalse();
    });
});
