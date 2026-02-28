<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\StyleAnalysisResult;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->seed(PlanSeeder::class);

    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // AI Learning requires Professional plan
    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'plan_id' => PlanSeeder::PROFESSIONAL_PLAN_ID,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->startOfMonth()->toDateTimeString(),
        'current_period_end' => now()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    // Mock StyleProfileAnalyzerInterface
    $mockAnalyzer = Mockery::mock(StyleProfileAnalyzerInterface::class);
    $mockAnalyzer->shouldReceive('analyzeEditPatterns')->andReturn(new StyleAnalysisResult(
        tonePreferences: ['preferred' => 'casual'],
        lengthPreferences: ['avg_preferred_length' => 280],
        vocabularyPreferences: ['added_words' => ['awesome']],
        structurePreferences: ['uses_emojis' => true],
        hashtagPreferences: ['avg_count' => 5],
        styleSummary: 'Casual tone with emojis',
        sampleSize: 15,
    ));
    $this->app->instance(StyleProfileAnalyzerInterface::class, $mockAnalyzer);
});

it('POST /ai-intelligence/style-profile — 200 generates new profile', function () {
    // Create 10+ edited feedbacks to meet minimum threshold
    $generationId = (string) Str::uuid();
    $now = now()->toDateTimeString();

    DB::table('ai_generations')->insert([
        'id' => $generationId,
        'organization_id' => $this->orgId,
        'user_id' => $this->user['id'],
        'type' => 'title',
        'input' => json_encode(['prompt' => 'test']),
        'output' => json_encode(['title' => 'Test']),
        'model_used' => 'gpt-4o',
        'tokens_input' => 10,
        'tokens_output' => 10,
        'duration_ms' => 100,
        'cost_estimate' => 0.001,
        'created_at' => $now,
    ]);

    for ($i = 0; $i < 12; $i++) {
        DB::table('generation_feedback')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->orgId,
            'user_id' => $this->user['id'],
            'ai_generation_id' => $generationId,
            'action' => 'edited',
            'original_output' => json_encode(['title' => 'Original']),
            'edited_output' => json_encode(['title' => "Edited {$i}"]),
            'generation_type' => 'title',
            'created_at' => $now,
        ]);
    }

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai-intelligence/style-profile', [
        'generation_type' => 'title',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.type', 'style_profile')
        ->assertJsonPath('data.attributes.style_summary', 'Casual tone with emojis');
});

it('POST /ai-intelligence/style-profile — 422 missing generation_type', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai-intelligence/style-profile', []);

    $response->assertStatus(422);
});

it('POST /ai-intelligence/style-profile — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai-intelligence/style-profile', [
        'generation_type' => 'title',
    ]);

    $response->assertStatus(401);
});
