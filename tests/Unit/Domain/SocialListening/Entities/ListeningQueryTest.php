<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Events\ListeningQueryCreated;
use App\Domain\SocialListening\Events\ListeningQueryPaused;
use App\Domain\SocialListening\Events\ListeningQueryResumed;
use App\Domain\SocialListening\Exceptions\InvalidQueryTransitionException;
use App\Domain\SocialListening\Exceptions\QueryAlreadyDeletedException;
use App\Domain\SocialListening\ValueObjects\QueryStatus;
use App\Domain\SocialListening\ValueObjects\QueryType;

function createListeningQuery(array $overrides = []): ListeningQuery
{
    return ListeningQuery::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        name: $overrides['name'] ?? 'Brand mentions',
        type: $overrides['type'] ?? QueryType::Keyword,
        value: $overrides['value'] ?? 'my-brand',
        platforms: $overrides['platforms'] ?? ['instagram', 'tiktok'],
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates with domain events', function () {
    $query = createListeningQuery();

    expect($query->status)->toBe(QueryStatus::Active)
        ->and($query->name)->toBe('Brand mentions')
        ->and($query->type)->toBe(QueryType::Keyword)
        ->and($query->value)->toBe('my-brand')
        ->and($query->platforms)->toBe(['instagram', 'tiktok'])
        ->and($query->domainEvents)->toHaveCount(1)
        ->and($query->domainEvents[0])->toBeInstanceOf(ListeningQueryCreated::class);
});

it('pauses an active query', function () {
    $query = createListeningQuery();
    $paused = $query->pause('user-1');

    expect($paused->status)->toBe(QueryStatus::Paused)
        ->and($paused->domainEvents)->toHaveCount(1)
        ->and($paused->domainEvents[0])->toBeInstanceOf(ListeningQueryPaused::class)
        ->and($query->status)->toBe(QueryStatus::Active);
});

it('resumes a paused query', function () {
    $query = createListeningQuery();
    $paused = $query->pause('user-1');
    $resumed = $paused->resume('user-1');

    expect($resumed->status)->toBe(QueryStatus::Active)
        ->and($resumed->domainEvents)->toHaveCount(1)
        ->and($resumed->domainEvents[0])->toBeInstanceOf(ListeningQueryResumed::class);
});

it('marks as deleted', function () {
    $query = createListeningQuery();
    $deleted = $query->markDeleted();

    expect($deleted->status)->toBe(QueryStatus::Deleted)
        ->and($query->status)->toBe(QueryStatus::Active);
});

it('cannot pause a deleted query', function () {
    $query = createListeningQuery();
    $deleted = $query->markDeleted();

    $deleted->pause('user-1');
})->throws(InvalidQueryTransitionException::class);

it('cannot resume a deleted query', function () {
    $query = createListeningQuery();
    $deleted = $query->markDeleted();

    $deleted->resume('user-1');
})->throws(InvalidQueryTransitionException::class);

it('updates details', function () {
    $query = createListeningQuery();
    $updated = $query->updateDetails('New name', 'new-value', ['youtube']);

    expect($updated->name)->toBe('New name')
        ->and($updated->value)->toBe('new-value')
        ->and($updated->platforms)->toBe(['youtube'])
        ->and($updated->type)->toBe($query->type)
        ->and($updated->id)->toEqual($query->id);
});

it('reconstitutes', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $now = new DateTimeImmutable;

    $query = ListeningQuery::reconstitute(
        id: $id,
        organizationId: $orgId,
        name: 'Reconstituted',
        type: QueryType::Hashtag,
        value: '#test',
        platforms: ['instagram'],
        status: QueryStatus::Paused,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($query->id)->toEqual($id)
        ->and($query->organizationId)->toEqual($orgId)
        ->and($query->name)->toBe('Reconstituted')
        ->and($query->type)->toBe(QueryType::Hashtag)
        ->and($query->value)->toBe('#test')
        ->and($query->platforms)->toBe(['instagram'])
        ->and($query->status)->toBe(QueryStatus::Paused)
        ->and($query->domainEvents)->toBeEmpty();
});
