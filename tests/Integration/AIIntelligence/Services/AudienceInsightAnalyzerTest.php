<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\AudienceInsightAnalyzerInterface;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Infrastructure\AIIntelligence\Models\AudienceInsightModel;
use App\Infrastructure\AIIntelligence\Services\EloquentAudienceInsightAnalyzer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->orgId = (string) Str::uuid();
    $this->userId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'audience-test-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'audience-test-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->analyzer = new EloquentAudienceInsightAnalyzer();
});

it('returns default insights when no cached data exists', function () {
    $result = $this->analyzer->analyze([], InsightType::PreferredTopics, $this->orgId);

    expect($result->insightData)->toHaveKey('message')
        ->and($result->modelUsed)->toBeNull();
});

it('returns cached insights when valid', function () {
    AudienceInsightModel::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'social_account_id' => null,
        'insight_type' => 'preferred_topics',
        'insight_data' => [
            'topics' => [
                ['name' => 'Technology', 'score' => 0.85],
                ['name' => 'Design', 'score' => 0.72],
            ],
        ],
        'source_comment_count' => 100,
        'period_start' => now()->subDays(30),
        'period_end' => now(),
        'confidence_score' => 0.8,
        'generated_at' => now(),
        'expires_at' => now()->addDays(7),
        'created_at' => now(),
    ]);

    $result = $this->analyzer->analyze([], InsightType::PreferredTopics, $this->orgId);

    expect($result->insightData)->toHaveKey('topics')
        ->and($result->confidenceScore)->toBe(0.8)
        ->and($result->modelUsed)->toBe('cached');
});

it('returns default for expired insights', function () {
    AudienceInsightModel::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'social_account_id' => null,
        'insight_type' => 'preferred_topics',
        'insight_data' => ['topics' => []],
        'source_comment_count' => 100,
        'period_start' => now()->subDays(60),
        'period_end' => now()->subDays(30),
        'confidence_score' => 0.8,
        'generated_at' => now()->subDays(14),
        'expires_at' => now()->subDays(7), // Expired
        'created_at' => now()->subDays(14),
    ]);

    $result = $this->analyzer->analyze([], InsightType::PreferredTopics, $this->orgId);

    expect($result->insightData)->toHaveKey('message');
});

it('returns empty via static method', function () {
    $result = \App\Application\AIIntelligence\DTOs\AudienceInsightAnalysisResult::empty();

    expect($result->isEmpty())->toBeTrue()
        ->and($result->insightData)->toBeEmpty();
});

it('handles all insight types', function () {
    foreach (InsightType::cases() as $type) {
        $result = $this->analyzer->analyze([], $type, $this->orgId);
        expect($result->insightData)->toHaveKey('message');
    }
});

it('resolves from container', function () {
    $analyzer = app(AudienceInsightAnalyzerInterface::class);

    expect($analyzer)->toBeInstanceOf(EloquentAudienceInsightAnalyzer::class);
});
