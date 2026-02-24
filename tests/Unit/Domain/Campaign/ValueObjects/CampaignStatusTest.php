<?php

declare(strict_types=1);

use App\Domain\Campaign\ValueObjects\CampaignStatus;

it('allows draft to transition to active', function () {
    expect(CampaignStatus::Draft->canTransitionTo(CampaignStatus::Active))->toBeTrue();
});

it('allows draft to transition to archived', function () {
    expect(CampaignStatus::Draft->canTransitionTo(CampaignStatus::Archived))->toBeTrue();
});

it('allows active to transition to paused', function () {
    expect(CampaignStatus::Active->canTransitionTo(CampaignStatus::Paused))->toBeTrue();
});

it('allows active to transition to completed', function () {
    expect(CampaignStatus::Active->canTransitionTo(CampaignStatus::Completed))->toBeTrue();
});

it('does not allow completed to transition to draft', function () {
    expect(CampaignStatus::Completed->canTransitionTo(CampaignStatus::Draft))->toBeFalse();
});

it('does not allow archived to transition to active', function () {
    expect(CampaignStatus::Archived->canTransitionTo(CampaignStatus::Active))->toBeFalse();
});

it('returns editable for draft, active and paused', function () {
    expect(CampaignStatus::Draft->isEditable())->toBeTrue()
        ->and(CampaignStatus::Active->isEditable())->toBeTrue()
        ->and(CampaignStatus::Paused->isEditable())->toBeTrue()
        ->and(CampaignStatus::Completed->isEditable())->toBeFalse()
        ->and(CampaignStatus::Archived->isEditable())->toBeFalse();
});
