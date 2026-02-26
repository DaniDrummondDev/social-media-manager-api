<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Events\ContentProfileGenerated;
use App\Domain\AIIntelligence\Exceptions\ContentProfileExpiredException;
use App\Domain\AIIntelligence\Exceptions\InvalidSuggestionStatusTransitionException;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;
use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createContentProfile(array $overrides = []): ContentProfile
{
    return ContentProfile::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        socialAccountId: $overrides['socialAccountId'] ?? null,
        provider: $overrides['provider'] ?? 'instagram',
        userId: $overrides['userId'] ?? 'user-1',
    );
}

function reconstitutedProfile(array $overrides = []): ContentProfile
{
    return ContentProfile::reconstitute(
        id: $overrides['id'] ?? Uuid::generate(),
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        socialAccountId: $overrides['socialAccountId'] ?? null,
        provider: $overrides['provider'] ?? 'instagram',
        totalContentsAnalyzed: $overrides['totalContentsAnalyzed'] ?? 100,
        topThemes: $overrides['topThemes'] ?? [['theme' => 'tech', 'score' => 0.9, 'content_count' => 50]],
        engagementPatterns: $overrides['engagementPatterns'] ?? EngagementPattern::create(120, 30, 15, ['reel', 'carousel']),
        contentFingerprint: $overrides['contentFingerprint'] ?? ContentFingerprint::create(280, ['#tech'], ['informative' => 0.7], 3.5),
        highPerformerTraits: $overrides['highPerformerTraits'] ?? ['short_captions', 'morning_posts'],
        centroidEmbedding: $overrides['centroidEmbedding'] ?? [0.1, 0.2, 0.3],
        status: $overrides['status'] ?? ProfileStatus::Generated,
        generatedAt: $overrides['generatedAt'] ?? new DateTimeImmutable('-1 day'),
        expiresAt: $overrides['expiresAt'] ?? new DateTimeImmutable('+6 days'),
        createdAt: $overrides['createdAt'] ?? new DateTimeImmutable('-1 day'),
        updatedAt: $overrides['updatedAt'] ?? new DateTimeImmutable('-1 day'),
    );
}

it('creates a profile with domain event', function () {
    $profile = createContentProfile();

    expect($profile->status)->toBe(ProfileStatus::Generating)
        ->and($profile->totalContentsAnalyzed)->toBe(0)
        ->and($profile->topThemes)->toBe([])
        ->and($profile->engagementPatterns)->toBeNull()
        ->and($profile->contentFingerprint)->toBeNull()
        ->and($profile->domainEvents)->toHaveCount(1)
        ->and($profile->domainEvents[0])->toBeInstanceOf(ContentProfileGenerated::class);
});

it('creates a profile with provider and social account', function () {
    $orgId = Uuid::generate();
    $socialAccountId = Uuid::generate();

    $profile = ContentProfile::create(
        organizationId: $orgId,
        socialAccountId: $socialAccountId,
        provider: 'tiktok',
        userId: 'user-1',
    );

    expect($profile->provider)->toBe('tiktok')
        ->and((string) $profile->socialAccountId)->toBe((string) $socialAccountId)
        ->and((string) $profile->organizationId)->toBe((string) $orgId);
});

it('sets expiration to 7 days from creation', function () {
    $profile = createContentProfile();

    $diffInDays = $profile->generatedAt->diff($profile->expiresAt)->days;
    expect($diffInDays)->toBe(7);
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();

    $profile = reconstitutedProfile([
        'id' => $id,
        'organizationId' => $orgId,
        'status' => ProfileStatus::Generated,
        'totalContentsAnalyzed' => 150,
    ]);

    expect($profile->domainEvents)->toBeEmpty()
        ->and((string) $profile->id)->toBe((string) $id)
        ->and((string) $profile->organizationId)->toBe((string) $orgId)
        ->and($profile->status)->toBe(ProfileStatus::Generated)
        ->and($profile->totalContentsAnalyzed)->toBe(150);
});

it('completes a generating profile', function () {
    $profile = createContentProfile();
    $engagement = EngagementPattern::create(200, 50, 20, ['reel']);
    $fingerprint = ContentFingerprint::create(300, ['#ai'], ['inspirational' => 0.5], 2.0);

    $completed = $profile->complete(
        totalContentsAnalyzed: 200,
        topThemes: [['theme' => 'ai', 'score' => 0.95, 'content_count' => 80]],
        engagementPatterns: $engagement,
        contentFingerprint: $fingerprint,
        highPerformerTraits: ['video_content'],
        centroidEmbedding: [0.5, 0.6],
    );

    expect($completed->status)->toBe(ProfileStatus::Generated)
        ->and($completed->totalContentsAnalyzed)->toBe(200)
        ->and($completed->engagementPatterns)->toBe($engagement)
        ->and($completed->contentFingerprint)->toBe($fingerprint)
        ->and($completed->highPerformerTraits)->toBe(['video_content']);
});

it('cannot complete an already generated profile', function () {
    $profile = reconstitutedProfile(['status' => ProfileStatus::Generated]);
    $engagement = EngagementPattern::create(100, 20, 10, ['post']);
    $fingerprint = ContentFingerprint::create(200, [], [], 1.0);

    $profile->complete(50, [], $engagement, $fingerprint, [], null);
})->throws(InvalidSuggestionStatusTransitionException::class);

it('marks a generated profile as expired', function () {
    $profile = reconstitutedProfile(['status' => ProfileStatus::Generated]);

    $expired = $profile->markExpired();

    expect($expired->status)->toBe(ProfileStatus::Expired);
});

it('cannot mark an expired profile as expired again', function () {
    $profile = reconstitutedProfile(['status' => ProfileStatus::Expired]);

    $profile->markExpired();
})->throws(ContentProfileExpiredException::class);

it('cannot mark a generating profile as expired', function () {
    $profile = createContentProfile();

    $profile->markExpired();
})->throws(InvalidSuggestionStatusTransitionException::class);

it('detects expired profile by date', function () {
    $profile = reconstitutedProfile([
        'expiresAt' => new DateTimeImmutable('-1 day'),
    ]);

    expect($profile->isExpired())->toBeTrue();
});

it('detects non-expired profile', function () {
    $profile = reconstitutedProfile([
        'expiresAt' => new DateTimeImmutable('+3 days'),
    ]);

    expect($profile->isExpired())->toBeFalse();
});
