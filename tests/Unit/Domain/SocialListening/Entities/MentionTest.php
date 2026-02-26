<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Events\MentionDetected;
use App\Domain\SocialListening\Events\MentionFlagged;
use App\Domain\SocialListening\ValueObjects\Sentiment;

function createMention(array $overrides = []): Mention
{
    return Mention::create(
        queryId: $overrides['queryId'] ?? Uuid::generate(),
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        platform: $overrides['platform'] ?? 'instagram',
        externalId: $overrides['externalId'] ?? 'ext-mention-1',
        authorUsername: $overrides['authorUsername'] ?? 'johndoe',
        authorDisplayName: $overrides['authorDisplayName'] ?? 'John Doe',
        authorFollowerCount: $overrides['authorFollowerCount'] ?? 5000,
        profileUrl: $overrides['profileUrl'] ?? 'https://instagram.com/johndoe',
        content: $overrides['content'] ?? 'Loving this brand!',
        url: $overrides['url'] ?? 'https://instagram.com/p/123',
        sentiment: $overrides['sentiment'] ?? Sentiment::Positive,
        sentimentScore: $overrides['sentimentScore'] ?? 0.85,
        reach: $overrides['reach'] ?? 1000,
        engagementCount: $overrides['engagementCount'] ?? 50,
        publishedAt: $overrides['publishedAt'] ?? new DateTimeImmutable,
    );
}

it('creates with domain events', function () {
    $mention = createMention();

    expect($mention->isFlagged)->toBeFalse()
        ->and($mention->isRead)->toBeFalse()
        ->and($mention->platform)->toBe('instagram')
        ->and($mention->content)->toBe('Loving this brand!')
        ->and($mention->sentiment)->toBe(Sentiment::Positive)
        ->and($mention->domainEvents)->toHaveCount(1)
        ->and($mention->domainEvents[0])->toBeInstanceOf(MentionDetected::class);
});

it('flags a mention', function () {
    $mention = createMention();
    $flagged = $mention->flag('user-1');

    expect($flagged->isFlagged)->toBeTrue()
        ->and($flagged->domainEvents)->toHaveCount(1)
        ->and($flagged->domainEvents[0])->toBeInstanceOf(MentionFlagged::class)
        ->and($mention->isFlagged)->toBeFalse();
});

it('unflags a mention', function () {
    $mention = createMention();
    $flagged = $mention->flag('user-1');
    $unflagged = $flagged->unflag();

    expect($unflagged->isFlagged)->toBeFalse()
        ->and($flagged->isFlagged)->toBeTrue();
});

it('marks as read', function () {
    $mention = createMention();
    $read = $mention->markAsRead();

    expect($read->isRead)->toBeTrue()
        ->and($mention->isRead)->toBeFalse();
});

it('reconstitutes', function () {
    $id = Uuid::generate();
    $queryId = Uuid::generate();
    $orgId = Uuid::generate();
    $publishedAt = new DateTimeImmutable('2025-01-15 10:00:00');
    $detectedAt = new DateTimeImmutable('2025-01-15 10:05:00');

    $mention = Mention::reconstitute(
        id: $id,
        queryId: $queryId,
        organizationId: $orgId,
        platform: 'tiktok',
        externalId: 'ext-456',
        authorUsername: 'janedoe',
        authorDisplayName: 'Jane Doe',
        authorFollowerCount: 100000,
        profileUrl: 'https://tiktok.com/@janedoe',
        content: 'Check this out!',
        url: 'https://tiktok.com/v/456',
        sentiment: Sentiment::Neutral,
        sentimentScore: 0.5,
        reach: 5000,
        engagementCount: 200,
        isFlagged: true,
        isRead: true,
        publishedAt: $publishedAt,
        detectedAt: $detectedAt,
    );

    expect($mention->id)->toEqual($id)
        ->and($mention->queryId)->toEqual($queryId)
        ->and($mention->organizationId)->toEqual($orgId)
        ->and($mention->platform)->toBe('tiktok')
        ->and($mention->authorUsername)->toBe('janedoe')
        ->and($mention->authorFollowerCount)->toBe(100000)
        ->and($mention->sentiment)->toBe(Sentiment::Neutral)
        ->and($mention->isFlagged)->toBeTrue()
        ->and($mention->isRead)->toBeTrue()
        ->and($mention->domainEvents)->toBeEmpty();
});
