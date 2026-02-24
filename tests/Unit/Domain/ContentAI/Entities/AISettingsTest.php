<?php

declare(strict_types=1);

use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\ContentAI\Events\AISettingsUpdated;
use App\Domain\ContentAI\ValueObjects\Language;
use App\Domain\ContentAI\ValueObjects\Tone;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates with defaults', function () {
    $settings = AISettings::create(organizationId: Uuid::generate());

    expect($settings->defaultTone)->toBe(Tone::Professional)
        ->and($settings->customToneDescription)->toBeNull()
        ->and($settings->defaultLanguage)->toBe(Language::PtBR)
        ->and($settings->monthlyGenerationLimit)->toBe(500)
        ->and($settings->domainEvents)->toBeEmpty();
});

it('creates with custom values', function () {
    $settings = AISettings::create(
        organizationId: Uuid::generate(),
        defaultTone: Tone::Custom,
        customToneDescription: 'My custom tone',
        defaultLanguage: Language::EnUS,
        monthlyGenerationLimit: 1000,
    );

    expect($settings->defaultTone)->toBe(Tone::Custom)
        ->and($settings->customToneDescription)->toBe('My custom tone')
        ->and($settings->defaultLanguage)->toBe(Language::EnUS)
        ->and($settings->monthlyGenerationLimit)->toBe(1000);
});

it('rejects custom tone without description', function () {
    AISettings::create(
        organizationId: Uuid::generate(),
        defaultTone: Tone::Custom,
    );
})->throws(InvalidArgumentException::class);

it('updates settings with event', function () {
    $settings = AISettings::create(organizationId: Uuid::generate());
    $updated = $settings->update(defaultTone: Tone::Casual, defaultLanguage: Language::EsES);

    expect($updated->defaultTone)->toBe(Tone::Casual)
        ->and($updated->defaultLanguage)->toBe(Language::EsES)
        ->and($updated->domainEvents)->toHaveCount(1)
        ->and($updated->domainEvents[0])->toBeInstanceOf(AISettingsUpdated::class);
});

it('rejects update to custom tone without description', function () {
    $settings = AISettings::create(organizationId: Uuid::generate());
    $settings->update(defaultTone: Tone::Custom);
})->throws(InvalidArgumentException::class);

it('releases events', function () {
    $settings = AISettings::create(organizationId: Uuid::generate());
    $updated = $settings->update(defaultTone: Tone::Fun);
    expect($updated->domainEvents)->toHaveCount(1);

    $released = $updated->releaseEvents();
    expect($released->domainEvents)->toBeEmpty();
});

it('reconstitutes without events', function () {
    $settings = AISettings::reconstitute(
        organizationId: Uuid::generate(),
        defaultTone: Tone::Professional,
        customToneDescription: null,
        defaultLanguage: Language::PtBR,
        monthlyGenerationLimit: 500,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect($settings->domainEvents)->toBeEmpty();
});
