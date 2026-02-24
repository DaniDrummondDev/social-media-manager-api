<?php

declare(strict_types=1);

use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Events\CampaignCreated;
use App\Domain\Campaign\Exceptions\InvalidStatusTransitionException;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createCampaign(
    ?string $name = null,
    ?CampaignStatus $status = null,
): Campaign {
    $campaign = Campaign::create(
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: $name ?? 'Test Campaign',
        description: 'A test campaign',
        tags: ['test', 'campaign'],
    );

    if ($status !== null && $status !== CampaignStatus::Draft) {
        $campaign = $campaign->update(status: $status);
    }

    return $campaign;
}

it('creates campaign with draft status and event', function () {
    $campaign = createCampaign();

    expect($campaign->status)->toBe(CampaignStatus::Draft)
        ->and($campaign->name)->toBe('Test Campaign')
        ->and($campaign->description)->toBe('A test campaign')
        ->and($campaign->tags)->toBe(['test', 'campaign'])
        ->and($campaign->deletedAt)->toBeNull()
        ->and($campaign->purgeAt)->toBeNull()
        ->and($campaign->domainEvents)->toHaveCount(1)
        ->and($campaign->domainEvents[0])->toBeInstanceOf(CampaignCreated::class);
});

it('reconstitutes without events', function () {
    $now = new DateTimeImmutable;
    $campaign = Campaign::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: 'Reconstituted',
        description: null,
        startsAt: null,
        endsAt: null,
        status: CampaignStatus::Active,
        tags: [],
        createdAt: $now,
        updatedAt: $now,
        deletedAt: null,
        purgeAt: null,
    );

    expect($campaign->name)->toBe('Reconstituted')
        ->and($campaign->status)->toBe(CampaignStatus::Active)
        ->and($campaign->domainEvents)->toBeEmpty();
});

it('updates campaign name and description', function () {
    $campaign = createCampaign();
    $updated = $campaign->update(name: 'Updated Name', description: 'Updated Desc');

    expect($updated->name)->toBe('Updated Name')
        ->and($updated->description)->toBe('Updated Desc')
        ->and($updated->id)->toBe($campaign->id);
});

it('transitions status from draft to active', function () {
    $campaign = createCampaign();
    $updated = $campaign->update(status: CampaignStatus::Active);

    expect($updated->status)->toBe(CampaignStatus::Active);
});

it('rejects invalid status transition', function () {
    $campaign = createCampaign();
    $campaign->update(status: CampaignStatus::Completed);
})->throws(InvalidStatusTransitionException::class);

it('soft deletes with purge date', function () {
    $campaign = createCampaign();
    $deleted = $campaign->softDelete(30);

    expect($deleted->deletedAt)->not->toBeNull()
        ->and($deleted->purgeAt)->not->toBeNull()
        ->and($deleted->isDeleted())->toBeTrue();
});

it('restores a soft deleted campaign', function () {
    $campaign = createCampaign();
    $deleted = $campaign->softDelete();
    $restored = $deleted->restore();

    expect($restored->deletedAt)->toBeNull()
        ->and($restored->purgeAt)->toBeNull()
        ->and($restored->isDeleted())->toBeFalse();
});

it('returns same instance when restoring non-deleted campaign', function () {
    $campaign = createCampaign();
    $restored = $campaign->restore();

    expect($restored)->toBe($campaign);
});

it('reports isEditable correctly', function () {
    $campaign = createCampaign();

    expect($campaign->isEditable())->toBeTrue();

    $deleted = $campaign->softDelete();
    expect($deleted->isEditable())->toBeFalse();
});

it('reports isPurgeable correctly', function () {
    $campaign = createCampaign();

    expect($campaign->isPurgeable())->toBeFalse();

    $deleted = $campaign->softDelete(0);
    expect($deleted->isPurgeable())->toBeTrue();
});

it('releases events', function () {
    $campaign = createCampaign();
    expect($campaign->domainEvents)->toHaveCount(1);

    $released = $campaign->releaseEvents();
    expect($released->domainEvents)->toBeEmpty();
});
