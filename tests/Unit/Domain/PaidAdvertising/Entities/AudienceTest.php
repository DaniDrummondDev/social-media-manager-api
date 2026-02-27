<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Events\AudienceCreated;
use App\Domain\PaidAdvertising\Events\AudienceUpdated;
use App\Domain\PaidAdvertising\ValueObjects\DemographicFilter;
use App\Domain\PaidAdvertising\ValueObjects\InterestFilter;
use App\Domain\PaidAdvertising\ValueObjects\LocationFilter;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestTargetingSpecForAudience(): TargetingSpec
{
    return TargetingSpec::create(
        demographics: DemographicFilter::create(18, 45, ['male'], ['pt']),
        locations: LocationFilter::create(['BR']),
        interests: InterestFilter::create([['id' => '1', 'name' => 'Tech']]),
    );
}

function createTestAudience(): Audience
{
    return Audience::create(
        organizationId: Uuid::generate(),
        name: 'Young Males BR',
        targetingSpec: createTestTargetingSpecForAudience(),
        userId: (string) Uuid::generate(),
    );
}

it('creates audience with AudienceCreated event', function () {
    $audience = createTestAudience();

    expect($audience->name)->toBe('Young Males BR')
        ->and($audience->providerAudienceIds)->toBeNull()
        ->and($audience->domainEvents)->toHaveCount(1)
        ->and($audience->domainEvents[0])->toBeInstanceOf(AudienceCreated::class);
});

it('reconstitutes audience without events', function () {
    $audience = Audience::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Reconstituted',
        targetingSpec: createTestTargetingSpecForAudience(),
        providerAudienceIds: ['meta' => 'ext_001'],
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect($audience->name)->toBe('Reconstituted')
        ->and($audience->providerAudienceIds)->toBe(['meta' => 'ext_001'])
        ->and($audience->domainEvents)->toBeEmpty();
});

it('updates name and targeting spec with AudienceUpdated event', function () {
    $audience = createTestAudience();
    $newSpec = TargetingSpec::create(
        demographics: DemographicFilter::create(25, 55),
        locations: LocationFilter::create(['US']),
        interests: InterestFilter::create(),
    );

    $updated = $audience->update('Updated Audience', $newSpec, (string) Uuid::generate());

    expect($updated->name)->toBe('Updated Audience')
        ->and($updated->targetingSpec->demographics->ageMin)->toBe(25)
        ->and($updated->domainEvents)->toHaveCount(2)
        ->and($updated->domainEvents[1])->toBeInstanceOf(AudienceUpdated::class);
});

it('sets provider audience id', function () {
    $audience = createTestAudience();
    $withId = $audience->setProviderAudienceId('meta', 'ext_123');

    expect($withId->providerAudienceIds)->toBe(['meta' => 'ext_123']);
});

it('gets provider audience id when mapped', function () {
    $audience = Audience::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        name: 'Test',
        targetingSpec: createTestTargetingSpecForAudience(),
        providerAudienceIds: ['meta' => 'ext_123', 'tiktok' => 'tt_456'],
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect($audience->getProviderAudienceId('meta'))->toBe('ext_123')
        ->and($audience->getProviderAudienceId('tiktok'))->toBe('tt_456');
});

it('returns null for unmapped provider', function () {
    $audience = createTestAudience();

    expect($audience->getProviderAudienceId('google'))->toBeNull();
});

it('releases events returning clean instance', function () {
    $audience = createTestAudience();

    expect($audience->domainEvents)->toHaveCount(1);

    $released = $audience->releaseEvents();

    expect($released->domainEvents)->toBeEmpty()
        ->and($released->name)->toBe('Young Males BR');
});
