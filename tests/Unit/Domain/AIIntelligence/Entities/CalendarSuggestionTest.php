<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\CalendarSuggestion;
use App\Domain\AIIntelligence\Events\CalendarItemsAccepted;
use App\Domain\AIIntelligence\Events\CalendarSuggestionGenerated;
use App\Domain\AIIntelligence\Exceptions\CalendarSuggestionExpiredException;
use App\Domain\AIIntelligence\Exceptions\InvalidSuggestionStatusTransitionException;
use App\Domain\AIIntelligence\ValueObjects\CalendarItem;
use App\Domain\AIIntelligence\ValueObjects\SuggestionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createSuggestion(array $overrides = []): CalendarSuggestion
{
    return CalendarSuggestion::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        periodStart: $overrides['periodStart'] ?? new DateTimeImmutable('+1 day'),
        periodEnd: $overrides['periodEnd'] ?? new DateTimeImmutable('+7 days'),
        userId: $overrides['userId'] ?? 'user-1',
    );
}

function reconstituteSuggestion(array $overrides = []): CalendarSuggestion
{
    return CalendarSuggestion::reconstitute(
        id: $overrides['id'] ?? Uuid::generate(),
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        periodStart: $overrides['periodStart'] ?? new DateTimeImmutable('+1 day'),
        periodEnd: $overrides['periodEnd'] ?? new DateTimeImmutable('+7 days'),
        suggestions: $overrides['suggestions'] ?? [],
        basedOn: $overrides['basedOn'] ?? [],
        status: $overrides['status'] ?? SuggestionStatus::Generating,
        acceptedItems: $overrides['acceptedItems'] ?? null,
        generatedAt: $overrides['generatedAt'] ?? new DateTimeImmutable,
        expiresAt: $overrides['expiresAt'] ?? new DateTimeImmutable('+7 days'),
        createdAt: $overrides['createdAt'] ?? new DateTimeImmutable,
    );
}

function createCalendarItems(): array
{
    return [
        CalendarItem::create('2026-03-01', ['topic-1'], 'post', ['instagram'], 'Reason 1', 1),
        CalendarItem::create('2026-03-02', ['topic-2'], 'reel', ['tiktok'], 'Reason 2', 2),
    ];
}

it('creates with Generating status, empty suggestions and CalendarSuggestionGenerated event', function () {
    $suggestion = createSuggestion();

    expect($suggestion->status)->toBe(SuggestionStatus::Generating)
        ->and($suggestion->suggestions)->toBeEmpty()
        ->and($suggestion->basedOn)->toBeEmpty()
        ->and($suggestion->acceptedItems)->toBeNull()
        ->and($suggestion->domainEvents)->toHaveCount(1)
        ->and($suggestion->domainEvents[0])->toBeInstanceOf(CalendarSuggestionGenerated::class);
});

it('creates with expiresAt 7 days from now', function () {
    $before = new DateTimeImmutable('+7 days');
    $suggestion = createSuggestion();
    $after = new DateTimeImmutable('+7 days');

    expect($suggestion->expiresAt)->toBeGreaterThanOrEqual($before)
        ->and($suggestion->expiresAt)->toBeLessThanOrEqual($after);
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();

    $suggestion = reconstituteSuggestion([
        'id' => $id,
        'organizationId' => $orgId,
        'status' => SuggestionStatus::Generated,
    ]);

    expect($suggestion->id)->toEqual($id)
        ->and($suggestion->organizationId)->toEqual($orgId)
        ->and($suggestion->status)->toBe(SuggestionStatus::Generated)
        ->and($suggestion->domainEvents)->toBeEmpty();
});

it('reconstitutes preserving all fields', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $items = createCalendarItems();
    $now = new DateTimeImmutable;

    $suggestion = CalendarSuggestion::reconstitute(
        id: $id,
        organizationId: $orgId,
        periodStart: new DateTimeImmutable('2026-03-01'),
        periodEnd: new DateTimeImmutable('2026-03-07'),
        suggestions: $items,
        basedOn: ['analytics' => true],
        status: SuggestionStatus::Accepted,
        acceptedItems: [0, 1],
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
    );

    expect($suggestion->id)->toEqual($id)
        ->and($suggestion->organizationId)->toEqual($orgId)
        ->and($suggestion->periodStart->format('Y-m-d'))->toBe('2026-03-01')
        ->and($suggestion->periodEnd->format('Y-m-d'))->toBe('2026-03-07')
        ->and($suggestion->suggestions)->toHaveCount(2)
        ->and($suggestion->basedOn)->toBe(['analytics' => true])
        ->and($suggestion->status)->toBe(SuggestionStatus::Accepted)
        ->and($suggestion->acceptedItems)->toBe([0, 1]);
});

it('completes from Generating to Generated with suggestions and basedOn', function () {
    $suggestion = createSuggestion();
    $items = createCalendarItems();
    $basedOn = ['analytics' => true, 'trends' => ['topic-1']];

    $completed = $suggestion->complete($items, $basedOn);

    expect($completed->status)->toBe(SuggestionStatus::Generated)
        ->and($completed->suggestions)->toHaveCount(2)
        ->and($completed->basedOn)->toBe($basedOn)
        ->and($completed->id)->toEqual($suggestion->id);
});

it('complete throws InvalidSuggestionStatusTransitionException from Generated', function () {
    $suggestion = reconstituteSuggestion(['status' => SuggestionStatus::Generated]);

    $suggestion->complete([], []);
})->throws(InvalidSuggestionStatusTransitionException::class);

it('markReviewed transitions from Generated to Reviewed', function () {
    $suggestion = reconstituteSuggestion(['status' => SuggestionStatus::Generated]);

    $reviewed = $suggestion->markReviewed();

    expect($reviewed->status)->toBe(SuggestionStatus::Reviewed)
        ->and($reviewed->id)->toEqual($suggestion->id);
});

it('markReviewed throws from Generating', function () {
    $suggestion = reconstituteSuggestion(['status' => SuggestionStatus::Generating]);

    $suggestion->markReviewed();
})->throws(InvalidSuggestionStatusTransitionException::class);

it('acceptItems transitions from Generated to Accepted with CalendarItemsAccepted event', function () {
    $items = createCalendarItems();
    $suggestion = reconstituteSuggestion([
        'status' => SuggestionStatus::Generated,
        'suggestions' => $items,
    ]);

    $accepted = $suggestion->acceptItems([0], 'user-1');

    expect($accepted->status)->toBe(SuggestionStatus::Accepted)
        ->and($accepted->acceptedItems)->toBe([0])
        ->and($accepted->domainEvents)->toHaveCount(1)
        ->and($accepted->domainEvents[0])->toBeInstanceOf(CalendarItemsAccepted::class)
        ->and($accepted->domainEvents[0]->acceptedCount)->toBe(1)
        ->and($accepted->domainEvents[0]->totalCount)->toBe(2);
});

it('acceptItems transitions from Reviewed to Accepted', function () {
    $items = createCalendarItems();
    $suggestion = reconstituteSuggestion([
        'status' => SuggestionStatus::Reviewed,
        'suggestions' => $items,
    ]);

    $accepted = $suggestion->acceptItems([0, 1], 'user-1');

    expect($accepted->status)->toBe(SuggestionStatus::Accepted)
        ->and($accepted->acceptedItems)->toBe([0, 1]);
});

it('acceptItems throws CalendarSuggestionExpiredException when expired', function () {
    $suggestion = reconstituteSuggestion([
        'status' => SuggestionStatus::Generated,
        'expiresAt' => new DateTimeImmutable('-1 day'),
    ]);

    $suggestion->acceptItems([0], 'user-1');
})->throws(CalendarSuggestionExpiredException::class);

it('acceptItems throws InvalidSuggestionStatusTransitionException from Generating', function () {
    $suggestion = reconstituteSuggestion(['status' => SuggestionStatus::Generating]);

    $suggestion->acceptItems([0], 'user-1');
})->throws(InvalidSuggestionStatusTransitionException::class);

it('markExpired transitions from Generated to Expired', function () {
    $suggestion = reconstituteSuggestion(['status' => SuggestionStatus::Generated]);

    $expired = $suggestion->markExpired();

    expect($expired->status)->toBe(SuggestionStatus::Expired)
        ->and($expired->id)->toEqual($suggestion->id);
});

it('markExpired transitions from Reviewed to Expired', function () {
    $suggestion = reconstituteSuggestion(['status' => SuggestionStatus::Reviewed]);

    $expired = $suggestion->markExpired();

    expect($expired->status)->toBe(SuggestionStatus::Expired);
});

it('markExpired throws from Accepted (terminal)', function () {
    $suggestion = reconstituteSuggestion(['status' => SuggestionStatus::Accepted]);

    $suggestion->markExpired();
})->throws(InvalidSuggestionStatusTransitionException::class);

it('isExpired returns true when expiresAt in the past', function () {
    $suggestion = reconstituteSuggestion([
        'expiresAt' => new DateTimeImmutable('-1 hour'),
    ]);

    expect($suggestion->isExpired())->toBeTrue();
});

it('isExpired returns false when expiresAt in the future', function () {
    $suggestion = reconstituteSuggestion([
        'expiresAt' => new DateTimeImmutable('+7 days'),
    ]);

    expect($suggestion->isExpired())->toBeFalse();
});
