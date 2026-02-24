<?php

declare(strict_types=1);

use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\ContentAI\ValueObjects\Language;
use App\Domain\ContentAI\ValueObjects\Tone;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(AISettingsRepositoryInterface::class);
    $this->orgId = (string) Uuid::generate();

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'settings-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('returns null when no settings exist', function () {
    expect($this->repository->findByOrganizationId(Uuid::fromString($this->orgId)))->toBeNull();
});

it('upserts and retrieves settings', function () {
    $settings = AISettings::create(
        organizationId: Uuid::fromString($this->orgId),
        defaultTone: Tone::Casual,
        defaultLanguage: Language::EnUS,
    );

    $this->repository->upsert($settings);

    $found = $this->repository->findByOrganizationId(Uuid::fromString($this->orgId));

    expect($found)->not->toBeNull()
        ->and($found->defaultTone)->toBe(Tone::Casual)
        ->and($found->defaultLanguage)->toBe(Language::EnUS)
        ->and($found->monthlyGenerationLimit)->toBe(500);
});

it('updates existing settings via upsert', function () {
    $settings = AISettings::create(organizationId: Uuid::fromString($this->orgId));
    $this->repository->upsert($settings);

    $updated = $settings->update(defaultTone: Tone::Fun, defaultLanguage: Language::EsES);
    $this->repository->upsert($updated);

    $found = $this->repository->findByOrganizationId(Uuid::fromString($this->orgId));
    expect($found->defaultTone)->toBe(Tone::Fun)
        ->and($found->defaultLanguage)->toBe(Language::EsES);
});
