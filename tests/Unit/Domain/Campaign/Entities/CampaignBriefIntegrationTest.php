<?php

declare(strict_types=1);

use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates campaign without brief', function () {
    $campaign = Campaign::create(organizationId: Uuid::generate(), createdBy: Uuid::generate(), name: 'Test Campaign');
    expect($campaign->brief)->toBeNull();
});

it('creates campaign with brief', function () {
    $brief = new CampaignBrief(text: 'Black Friday campaign', targetAudience: 'Young adults', restrictions: null, cta: 'Buy now');
    $campaign = Campaign::create(organizationId: Uuid::generate(), createdBy: Uuid::generate(), name: 'Test Campaign', brief: $brief);
    expect($campaign->brief)->not->toBeNull()
        ->and($campaign->brief->text)->toBe('Black Friday campaign')
        ->and($campaign->brief->targetAudience)->toBe('Young adults')
        ->and($campaign->brief->cta)->toBe('Buy now');
});

it('update preserves existing brief when not provided', function () {
    $brief = new CampaignBrief(text: 'Original brief', targetAudience: null, restrictions: null, cta: null);
    $campaign = Campaign::create(organizationId: Uuid::generate(), createdBy: Uuid::generate(), name: 'Test Campaign', brief: $brief);
    $updated = $campaign->update(name: 'Updated Name');
    expect($updated->name)->toBe('Updated Name')
        ->and($updated->brief)->not->toBeNull()
        ->and($updated->brief->text)->toBe('Original brief');
});

it('update replaces brief when provided', function () {
    $campaign = Campaign::create(organizationId: Uuid::generate(), createdBy: Uuid::generate(), name: 'Test Campaign', brief: new CampaignBrief('Old', null, null, null));
    $newBrief = new CampaignBrief('New brief', 'Teens', null, null);
    $updated = $campaign->update(brief: $newBrief);
    expect($updated->brief->text)->toBe('New brief')
        ->and($updated->brief->targetAudience)->toBe('Teens');
});

it('reconstitute includes brief', function () {
    $brief = new CampaignBrief('Reconstituted brief', null, null, null);
    $now = new DateTimeImmutable;
    $campaign = Campaign::reconstitute(
        id: Uuid::generate(), organizationId: Uuid::generate(), createdBy: Uuid::generate(),
        name: 'Test', description: null, startsAt: null, endsAt: null,
        status: CampaignStatus::Draft, tags: [], createdAt: $now, updatedAt: $now,
        deletedAt: null, purgeAt: null, brief: $brief,
    );
    expect($campaign->brief)->not->toBeNull()
        ->and($campaign->brief->text)->toBe('Reconstituted brief');
});
