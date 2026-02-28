<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Infrastructure\AIIntelligence\Models\OrgStyleProfileModel;
use App\Infrastructure\AIIntelligence\Services\EloquentStyleProfileAnalyzer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->orgId = (string) Str::uuid();
    $this->userId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'style-test-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'style-test-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->analyzer = new EloquentStyleProfileAnalyzer();
});

it('returns default style when no profile exists', function () {
    $result = $this->analyzer->analyzeEditPatterns($this->orgId, 'title');

    expect($result->sampleSize)->toBe(0)
        ->and($result->styleSummary)->toBeNull()
        ->and($result->tonePreferences['preferred'])->toBe('neutral');
});

it('returns cached style profile when valid', function () {
    OrgStyleProfileModel::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'sample_size' => 50,
        'tone_preferences' => ['preferred' => 'casual', 'avoids' => ['formal']],
        'length_preferences' => ['avg_preferred_length' => 100],
        'vocabulary_preferences' => ['added_words' => ['awesome']],
        'structure_preferences' => ['uses_emojis' => true],
        'hashtag_preferences' => ['avg_count' => 10, 'style' => 'branded'],
        'style_summary' => 'Casual and emoji-heavy style.',
        'generated_at' => now(),
        'expires_at' => now()->addDays(7),
    ]);

    $result = $this->analyzer->analyzeEditPatterns($this->orgId, 'title');

    expect($result->sampleSize)->toBe(50)
        ->and($result->styleSummary)->toBe('Casual and emoji-heavy style.')
        ->and($result->tonePreferences['preferred'])->toBe('casual')
        ->and($result->structurePreferences['uses_emojis'])->toBeTrue();
});

it('returns default style when profile is expired', function () {
    OrgStyleProfileModel::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'sample_size' => 50,
        'tone_preferences' => ['preferred' => 'casual'],
        'length_preferences' => [],
        'vocabulary_preferences' => [],
        'structure_preferences' => [],
        'hashtag_preferences' => [],
        'style_summary' => 'Expired profile.',
        'generated_at' => now()->subDays(14),
        'expires_at' => now()->subDays(7), // Expired
    ]);

    $result = $this->analyzer->analyzeEditPatterns($this->orgId, 'title');

    expect($result->sampleSize)->toBe(0)
        ->and($result->styleSummary)->toBeNull();
});

it('returns empty via static method', function () {
    $result = \App\Application\AIIntelligence\DTOs\StyleAnalysisResult::empty();

    expect($result->isEmpty())->toBeTrue()
        ->and($result->sampleSize)->toBe(0);
});

it('resolves from container', function () {
    $analyzer = app(StyleProfileAnalyzerInterface::class);

    expect($analyzer)->toBeInstanceOf(EloquentStyleProfileAnalyzer::class);
});
