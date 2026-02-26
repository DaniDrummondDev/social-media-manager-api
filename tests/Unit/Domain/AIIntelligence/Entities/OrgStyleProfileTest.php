<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\OrgStyleProfile;
use App\Domain\AIIntelligence\Events\OrgStyleProfileGenerated;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\AIIntelligence\ValueObjects\StylePreferences;
use App\Domain\Shared\ValueObjects\Uuid;

function createStyleProfile(array $overrides = []): OrgStyleProfile
{
    return OrgStyleProfile::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        generationType: $overrides['generationType'] ?? 'title',
        sampleSize: $overrides['sampleSize'] ?? 30,
        stylePreferences: $overrides['stylePreferences'] ?? StylePreferences::fromArray([]),
        styleSummary: $overrides['styleSummary'] ?? 'Casual tone with emojis',
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates with OrgStyleProfileGenerated event', function () {
    $profile = createStyleProfile(['sampleSize' => 30]);

    expect($profile->generationType)->toBe('title')
        ->and($profile->sampleSize)->toBe(30)
        ->and($profile->styleSummary)->toBe('Casual tone with emojis')
        ->and($profile->domainEvents)->toHaveCount(1)
        ->and($profile->domainEvents[0])->toBeInstanceOf(OrgStyleProfileGenerated::class);
});

it('determines Low confidence for fewer than 10 samples', function () {
    $profile = createStyleProfile(['sampleSize' => 5]);

    expect($profile->confidenceLevel)->toBe(ConfidenceLevel::Low);
});

it('determines Medium confidence for 10-50 samples', function () {
    $medium10 = createStyleProfile(['sampleSize' => 10]);
    $medium50 = createStyleProfile(['sampleSize' => 50]);

    expect($medium10->confidenceLevel)->toBe(ConfidenceLevel::Medium)
        ->and($medium50->confidenceLevel)->toBe(ConfidenceLevel::Medium);
});

it('determines High confidence for 51+ samples', function () {
    $profile = createStyleProfile(['sampleSize' => 51]);

    expect($profile->confidenceLevel)->toBe(ConfidenceLevel::High);
});

it('sets TTL to 14 days from now', function () {
    $profile = createStyleProfile();

    $daysDiff = $profile->generatedAt->diff($profile->expiresAt)->days;

    expect($daysDiff)->toBe(14);
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $profile = OrgStyleProfile::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        generationType: 'description',
        sampleSize: 20,
        stylePreferences: StylePreferences::fromArray([]),
        styleSummary: null,
        confidenceLevel: ConfidenceLevel::Medium,
        generatedAt: $now,
        expiresAt: $now->modify('+14 days'),
        createdAt: $now,
        updatedAt: $now,
    );

    expect($profile->id)->toEqual($id)
        ->and($profile->domainEvents)->toBeEmpty()
        ->and($profile->confidenceLevel)->toBe(ConfidenceLevel::Medium);
});

it('refreshes with new data and event', function () {
    $profile = createStyleProfile(['sampleSize' => 20]);

    $newPrefs = StylePreferences::fromArray([
        'tone_preferences' => ['preferred' => 'professional'],
    ]);

    $refreshed = $profile->refresh(
        sampleSize: 60,
        stylePreferences: $newPrefs,
        styleSummary: 'Professional tone',
        userId: 'user-2',
    );

    expect($refreshed->id)->toEqual($profile->id)
        ->and($refreshed->sampleSize)->toBe(60)
        ->and($refreshed->confidenceLevel)->toBe(ConfidenceLevel::High)
        ->and($refreshed->styleSummary)->toBe('Professional tone')
        ->and($refreshed->domainEvents)->toHaveCount(1)
        ->and($refreshed->domainEvents[0])->toBeInstanceOf(OrgStyleProfileGenerated::class)
        ->and($refreshed->domainEvents[0]->confidenceLevel)->toBe('high');
});

it('isExpired returns false for fresh profile', function () {
    $profile = createStyleProfile();

    expect($profile->isExpired())->toBeFalse();
});

it('isExpired returns true for expired profile', function () {
    $now = new DateTimeImmutable;
    $past = $now->modify('-15 days');

    $profile = OrgStyleProfile::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        sampleSize: 20,
        stylePreferences: StylePreferences::fromArray([]),
        styleSummary: null,
        confidenceLevel: ConfidenceLevel::Medium,
        generatedAt: $past,
        expiresAt: $past->modify('+14 days'), // 1 day ago
        createdAt: $past,
        updatedAt: $past,
    );

    expect($profile->isExpired())->toBeTrue();
});

it('hasEnoughData checks minimum 10 edits', function () {
    expect(OrgStyleProfile::hasEnoughData(9))->toBeFalse()
        ->and(OrgStyleProfile::hasEnoughData(10))->toBeTrue()
        ->and(OrgStyleProfile::hasEnoughData(100))->toBeTrue();
});

it('returns minEditsRequired constant', function () {
    expect(OrgStyleProfile::minEditsRequired())->toBe(10);
});
