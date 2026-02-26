<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\PredictionValidatorInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Mock PredictionValidatorInterface
    $mockValidator = Mockery::mock(PredictionValidatorInterface::class);
    $mockValidator->shouldReceive('normalizeEngagementRate')->andReturn(75);
    $this->app->instance(PredictionValidatorInterface::class, $mockValidator);
});

it('POST /ai-intelligence/prediction-validations — 201 validates prediction', function () {
    // Create required parent records (campaign → content → prediction)
    $predictionId = (string) Str::uuid();
    $contentId = (string) Str::uuid();
    $campaignId = (string) Str::uuid();
    $now = now()->toDateTimeString();

    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->user['id'],
        'name' => 'Test Campaign',
        'status' => 'draft',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('contents')->insert([
        'id' => $contentId,
        'organization_id' => $this->orgId,
        'campaign_id' => $campaignId,
        'created_by' => $this->user['id'],
        'status' => 'draft',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('performance_predictions')->insert([
        'id' => $predictionId,
        'organization_id' => $this->orgId,
        'content_id' => $contentId,
        'provider' => 'instagram',
        'overall_score' => 80,
        'breakdown' => json_encode([
            'content_similarity' => 80,
            'timing' => 70,
            'hashtags' => 75,
            'length' => 85,
            'media_type' => 90,
        ]),
        'model_version' => 'v1',
        'created_at' => $now,
    ]);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai-intelligence/prediction-validations', [
        'prediction_id' => $predictionId,
        'content_id' => $contentId,
        'provider' => 'instagram',
        'actual_engagement_rate' => 3.5,
        'metrics_snapshot' => ['likes' => 100, 'comments' => 10],
        'metrics_captured_at' => $now,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'prediction_validation')
        ->assertJsonPath('data.attributes.predicted_score', 80);
});

it('POST /ai-intelligence/prediction-validations — 422 missing required fields', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai-intelligence/prediction-validations', []);

    $response->assertStatus(422);
});

it('GET /ai-intelligence/prediction-accuracy — 200 returns accuracy metrics', function () {
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/ai-intelligence/prediction-accuracy');

    $response->assertOk()
        ->assertJsonPath('data.attributes.message', fn ($msg) => str_contains($msg, 'Insufficient') || str_contains($msg, 'available'));
});

it('POST /ai-intelligence/prediction-validations — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai-intelligence/prediction-validations', [
        'prediction_id' => (string) Str::uuid(),
        'content_id' => (string) Str::uuid(),
        'provider' => 'instagram',
        'actual_engagement_rate' => 3.5,
        'metrics_snapshot' => [],
        'metrics_captured_at' => now()->toDateTimeString(),
    ]);

    $response->assertStatus(401);
});
