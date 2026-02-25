<?php

declare(strict_types=1);

use App\Domain\Analytics\ValueObjects\ExportStatus;

it('allows Processing to transition to Ready', function () {
    expect(ExportStatus::Processing->canTransitionTo(ExportStatus::Ready))->toBeTrue();
});

it('allows Processing to transition to Failed', function () {
    expect(ExportStatus::Processing->canTransitionTo(ExportStatus::Failed))->toBeTrue();
});

it('does not allow Processing to transition to Expired', function () {
    expect(ExportStatus::Processing->canTransitionTo(ExportStatus::Expired))->toBeFalse();
});

it('allows Ready to transition to Expired', function () {
    expect(ExportStatus::Ready->canTransitionTo(ExportStatus::Expired))->toBeTrue();
});

it('does not allow Ready to transition to Failed', function () {
    expect(ExportStatus::Ready->canTransitionTo(ExportStatus::Failed))->toBeFalse();
});

it('does not allow Failed to transition', function () {
    expect(ExportStatus::Failed->canTransitionTo(ExportStatus::Ready))->toBeFalse()
        ->and(ExportStatus::Failed->canTransitionTo(ExportStatus::Expired))->toBeFalse();
});

it('does not allow Expired to transition', function () {
    expect(ExportStatus::Expired->canTransitionTo(ExportStatus::Ready))->toBeFalse();
});

it('identifies terminal statuses', function () {
    expect(ExportStatus::Failed->isTerminal())->toBeTrue()
        ->and(ExportStatus::Expired->isTerminal())->toBeTrue()
        ->and(ExportStatus::Processing->isTerminal())->toBeFalse()
        ->and(ExportStatus::Ready->isTerminal())->toBeFalse();
});
