<?php

declare(strict_types=1);

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

    // AI Intelligence requires Professional plan
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

    $this->campaignId = insertCampaignForPred($this->orgId, $this->user['id']);
    $this->contentId = insertContentForPred($this->orgId, $this->campaignId, $this->user['id']);
});

function insertCampaignForPred(string $orgId, string $userId): string
{
    $id = Str::uuid()->toString();

    DB::table('campaigns')->insert([
        'id' => $id,
        'organization_id' => $orgId,
        'created_by' => $userId,
        'name' => 'Test Campaign '.Str::random(4),
        'description' => null,
        'starts_at' => null,
        'ends_at' => null,
        'status' => 'draft',
        'tags' => json_encode([]),
        'deleted_at' => null,
        'purge_at' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    return $id;
}

function insertContentForPred(string $orgId, string $campaignId, string $userId): string
{
    $id = Str::uuid()->toString();

    DB::table('contents')->insert([
        'id' => $id,
        'organization_id' => $orgId,
        'campaign_id' => $campaignId,
        'created_by' => $userId,
        'title' => 'Test Content '.Str::random(4),
        'body' => 'Test body content',
        'hashtags' => json_encode([]),
        'status' => 'draft',
        'ai_generation_id' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'deleted_at' => null,
        'purge_at' => null,
    ]);

    return $id;
}

function insertContentProfileForPred(string $orgId, string $provider = 'instagram'): string
{
    $id = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('content_profiles')->insert([
        'id' => $id,
        'organization_id' => $orgId,
        'social_account_id' => null,
        'provider' => $provider,
        'status' => 'generated',
        'total_contents_analyzed' => 50,
        'top_themes' => json_encode([]),
        'engagement_patterns' => json_encode([
            'avg_likes' => 100, 'avg_comments' => 20,
            'avg_shares' => 10, 'best_content_types' => ['post'],
        ]),
        'content_fingerprint' => json_encode([
            'avg_length' => 200, 'hashtag_patterns' => [],
            'tone_distribution' => [], 'posting_frequency' => 1.0,
        ]),
        'high_performer_traits' => json_encode([]),
        'centroid_embedding' => null,
        'generated_at' => $now,
        'expires_at' => now()->addDays(7)->toDateTimeString(),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return $id;
}

function insertPrediction(string $orgId, string $contentId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();

    DB::table('performance_predictions')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'content_id' => $contentId,
        'provider' => 'instagram',
        'overall_score' => 75,
        'breakdown' => json_encode([
            'content_similarity' => 80, 'timing' => 70,
            'hashtags' => 60, 'length' => 75, 'media_type' => 90,
        ]),
        'similar_content_ids' => null,
        'recommendations' => json_encode([]),
        'model_version' => 'v1',
        'created_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('POST /contents/{id}/predict-performance — 200 with predictions', function () {
    insertContentProfileForPred($this->orgId, 'instagram');

    $response = $this->postJson("/api/v1/contents/{$this->contentId}/predict-performance", [
        'providers' => ['instagram'],
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBe(1)
        ->and($response->json('data.0.type'))->toBe('performance_prediction')
        ->and($response->json('data.0.attributes.provider'))->toBe('instagram')
        ->and($response->json('data.0.attributes.overall_score'))->toBeGreaterThanOrEqual(0);
});

it('POST /contents/{id}/predict-performance — 422 when no profile for provider', function () {
    $response = $this->postJson("/api/v1/contents/{$this->contentId}/predict-performance", [
        'providers' => ['youtube'],
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_DATA');
});

it('POST /contents/{id}/predict-performance — 422 validation errors', function () {
    $response = $this->postJson("/api/v1/contents/{$this->contentId}/predict-performance", [
        'providers' => [],
    ], $this->headers);

    $response->assertStatus(422);
});

it('GET /contents/{id}/predictions — 200 with predictions list', function () {
    insertPrediction($this->orgId, $this->contentId);
    insertPrediction($this->orgId, $this->contentId, ['provider' => 'tiktok', 'overall_score' => 60]);

    $response = $this->getJson("/api/v1/contents/{$this->contentId}/predictions", $this->headers);

    $response->assertStatus(200);

    expect(count($response->json('data')))->toBe(2);
});

it('GET /contents/{id}/predictions — 200 empty when no predictions', function () {
    $response = $this->getJson("/api/v1/contents/{$this->contentId}/predictions", $this->headers);

    $response->assertStatus(200);

    expect($response->json('data'))->toBeEmpty();
});

it('POST /contents/{id}/predict-performance — 401 unauthenticated', function () {
    $response = $this->postJson("/api/v1/contents/{$this->contentId}/predict-performance", [
        'providers' => ['instagram'],
    ]);

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org predictions', function () {
    insertPrediction($this->orgId, $this->contentId);

    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    $otherCampaignId = insertCampaignForPred($otherOrgId, $otherUser['id']);
    $otherContentId = insertContentForPred($otherOrgId, $otherCampaignId, $otherUser['id']);
    insertPrediction($otherOrgId, $otherContentId);

    $response = $this->getJson("/api/v1/contents/{$this->contentId}/predictions", $this->headers);

    $response->assertStatus(200);

    expect(count($response->json('data')))->toBe(1);
});
