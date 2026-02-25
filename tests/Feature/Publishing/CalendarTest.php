<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    $this->campaignId = insertCampaignCal($this->orgId, $this->user['id']);
    $this->contentId = insertContentCal($this->orgId, $this->user['id'], $this->campaignId, ['status' => 'scheduled']);
    $this->accountId = insertSocialAccountCal($this->orgId, $this->user['id']);
});

function insertCampaignCal(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('campaigns')->insert(array_merge([
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
    ], $overrides));

    return $id;
}

function insertContentCal(string $orgId, string $userId, string $campaignId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('contents')->insert(array_merge([
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
    ], $overrides));

    return $id;
}

function insertSocialAccountCal(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('social_accounts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'connected_by' => $userId,
        'provider' => 'instagram',
        'provider_user_id' => 'ig-'.Str::random(6),
        'username' => 'user_'.Str::random(4),
        'display_name' => 'Test Account',
        'profile_picture_url' => null,
        'access_token' => 'encrypted-access-token',
        'refresh_token' => 'encrypted-refresh-token',
        'token_expires_at' => now()->addHour()->toDateTimeString(),
        'scopes' => json_encode(['read', 'write']),
        'status' => 'connected',
        'last_synced_at' => null,
        'connected_at' => now()->toDateTimeString(),
        'disconnected_at' => null,
        'metadata' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'deleted_at' => null,
        'purge_at' => null,
    ], $overrides));

    return $id;
}

function insertScheduledPostCal(string $orgId, string $contentId, string $accountId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('scheduled_posts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'content_id' => $contentId,
        'social_account_id' => $accountId,
        'scheduled_by' => $userId,
        'scheduled_at' => now()->addHour()->toDateTimeString(),
        'status' => 'pending',
        'published_at' => null,
        'external_post_id' => null,
        'external_post_url' => null,
        'attempts' => 0,
        'max_attempts' => 3,
        'last_attempted_at' => null,
        'last_error' => null,
        'next_retry_at' => null,
        'dispatched_at' => null,
        'idempotency_key' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('returns calendar with month/year — 200', function () {
    $currentMonth = (int) date('m');
    $currentYear = (int) date('Y');

    // Insert a post scheduled for today
    insertScheduledPostCal($this->orgId, $this->contentId, $this->accountId, $this->user['id'], [
        'scheduled_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->getJson(
        "/api/v1/scheduled-posts/calendar?month={$currentMonth}&year={$currentYear}",
    );

    $response->assertOk()
        ->assertJsonStructure(['data' => ['period', 'days', 'total_posts']])
        ->assertJsonPath('data.total_posts', 1);
});

it('returns calendar with start_date/end_date — 200', function () {
    $startDate = now()->format('Y-m-d');
    $endDate = now()->addDays(7)->format('Y-m-d');

    insertScheduledPostCal($this->orgId, $this->contentId, $this->accountId, $this->user['id'], [
        'scheduled_at' => now()->addDays(2)->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->getJson(
        "/api/v1/scheduled-posts/calendar?start_date={$startDate}&end_date={$endDate}",
    );

    $response->assertOk()
        ->assertJsonStructure(['data' => ['period' => ['start', 'end'], 'days', 'total_posts']])
        ->assertJsonPath('data.total_posts', 1)
        ->assertJsonPath('data.period.start', $startDate)
        ->assertJsonPath('data.period.end', $endDate);
});

it('returns empty calendar — 200', function () {
    $response = $this->withHeaders($this->headers)->getJson(
        '/api/v1/scheduled-posts/calendar?month=1&year=2020',
    );

    $response->assertOk()
        ->assertJsonPath('data.total_posts', 0)
        ->assertJsonPath('data.days', []);
});

it('groups posts by day in calendar', function () {
    $today = now()->startOfDay();

    insertScheduledPostCal($this->orgId, $this->contentId, $this->accountId, $this->user['id'], [
        'scheduled_at' => $today->copy()->addHours(10)->toDateTimeString(),
    ]);
    insertScheduledPostCal($this->orgId, $this->contentId, $this->accountId, $this->user['id'], [
        'social_account_id' => insertSocialAccountCal($this->orgId, $this->user['id']),
        'scheduled_at' => $today->copy()->addHours(14)->toDateTimeString(),
    ]);

    $startDate = $today->format('Y-m-d');
    $endDate = $today->format('Y-m-d');

    $response = $this->withHeaders($this->headers)->getJson(
        "/api/v1/scheduled-posts/calendar?start_date={$startDate}&end_date={$endDate}",
    );

    $response->assertOk()
        ->assertJsonPath('data.total_posts', 2)
        ->assertJsonCount(1, 'data.days')
        ->assertJsonPath('data.days.0.count', 2);
});

it('defaults to current month when no params — 200', function () {
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/scheduled-posts/calendar');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['period', 'days', 'total_posts']]);
});

it('requires authentication for calendar — 401', function () {
    $response = $this->getJson('/api/v1/scheduled-posts/calendar');

    $response->assertStatus(401);
});
