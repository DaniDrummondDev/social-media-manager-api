<?php

declare(strict_types=1);

use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Bus;
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
});

function insertContentProfile(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('content_profiles')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'social_account_id' => null,
        'provider' => 'instagram',
        'status' => 'generated',
        'total_contents_analyzed' => 100,
        'top_themes' => json_encode([
            ['theme' => 'tech', 'score' => 0.9, 'content_count' => 50],
            ['theme' => 'ai', 'score' => 0.8, 'content_count' => 30],
        ]),
        'engagement_patterns' => json_encode([
            'avg_likes' => 120,
            'avg_comments' => 30,
            'avg_shares' => 15,
            'best_content_types' => ['reel', 'carousel'],
        ]),
        'content_fingerprint' => json_encode([
            'avg_length' => 280,
            'hashtag_patterns' => ['#tech', '#ai'],
            'tone_distribution' => ['informative' => 0.7, 'casual' => 0.3],
            'posting_frequency' => 3.5,
        ]),
        'high_performer_traits' => json_encode(['short_captions', 'morning_posts']),
        'centroid_embedding' => null,
        'generated_at' => $now,
        'expires_at' => now()->addDays(7)->toDateTimeString(),
        'created_at' => $now,
        'updated_at' => $now,
    ], $overrides));

    return $id;
}

it('POST /content-profile/generate — 202 with profile_id and status', function () {
    Bus::fake();

    $response = $this->postJson('/api/v1/ai-intelligence/content-profile/generate', [
        'provider' => 'instagram',
    ], $this->headers);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'data' => [
                'profile_id',
                'status',
                'message',
            ],
        ]);

    expect($response->json('data.status'))->toBe('generating')
        ->and($response->json('data.profile_id'))->toBeString();
});

it('GET /content-profile — 200 with profile data', function () {
    insertContentProfile($this->orgId);

    $response = $this->getJson('/api/v1/ai-intelligence/content-profile?provider=instagram', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'provider',
                    'total_contents_analyzed',
                    'top_themes',
                    'engagement_patterns',
                    'content_fingerprint',
                    'high_performer_traits',
                    'generated_at',
                    'expires_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('content_profile')
        ->and($response->json('data.attributes.provider'))->toBe('instagram')
        ->and($response->json('data.attributes.total_contents_analyzed'))->toBe(100);
});

it('GET /content-profile — 422 when no profile exists', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/content-profile?provider=youtube', $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'CONTENT_PROFILE_NOT_FOUND');
});

it('GET /content-profile/themes — 200 with themes', function () {
    insertContentProfile($this->orgId);

    $response = $this->getJson('/api/v1/ai-intelligence/content-profile/themes?provider=instagram&limit=1', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'themes',
            ],
        ]);

    expect($response->json('data.themes'))->toHaveCount(1)
        ->and($response->json('data.themes.0.theme'))->toBe('tech');
});

it('POST /content-profile/recommend — 422 when no profile', function () {
    $response = $this->postJson('/api/v1/ai-intelligence/content-profile/recommend', [
        'topic' => 'AI trends in social media',
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'CONTENT_PROFILE_NOT_FOUND');
});

it('POST /content-profile/generate — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai-intelligence/content-profile/generate', [
        'provider' => 'instagram',
    ]);

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org profiles', function () {
    insertContentProfile($this->orgId);

    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];
    insertContentProfile($otherOrgId, ['provider' => 'tiktok']);

    $response = $this->getJson('/api/v1/ai-intelligence/content-profile?provider=tiktok', $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'CONTENT_PROFILE_NOT_FOUND');
});
