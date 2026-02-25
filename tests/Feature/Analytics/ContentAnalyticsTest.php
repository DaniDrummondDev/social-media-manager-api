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

    $this->campaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $this->campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->user['id'],
        'name' => 'Test Campaign',
        'status' => 'draft',
        'tags' => json_encode([]),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->contentId = (string) Str::uuid();
    DB::table('contents')->insert([
        'id' => $this->contentId,
        'organization_id' => $this->orgId,
        'campaign_id' => $this->campaignId,
        'created_by' => $this->user['id'],
        'title' => 'Test Content',
        'body' => 'Body',
        'hashtags' => json_encode([]),
        'status' => 'ready',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('returns 200 with content analytics', function () {
    $response = $this->getJson("/api/v1/analytics/contents/{$this->contentId}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'content_id',
                'title',
                'networks',
                'last_synced_at',
            ],
        ]);
});

it('returns 422 when content not found', function () {
    $fakeId = (string) Str::uuid();
    $response = $this->getJson("/api/v1/analytics/contents/{$fakeId}", $this->headers);

    $response->assertStatus(422);
});

it('returns 422 for content in another org', function () {
    // Create another org + content
    $otherOrgId = (string) Str::uuid();
    DB::table('organizations')->insert([
        'id' => $otherOrgId,
        'name' => 'Other Org',
        'slug' => 'other-'.Str::random(4),
        'timezone' => 'UTC',
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $otherCampaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $otherCampaignId,
        'organization_id' => $otherOrgId,
        'created_by' => $this->user['id'],
        'name' => 'Other Campaign',
        'status' => 'draft',
        'tags' => json_encode([]),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $otherContentId = (string) Str::uuid();
    DB::table('contents')->insert([
        'id' => $otherContentId,
        'organization_id' => $otherOrgId,
        'campaign_id' => $otherCampaignId,
        'created_by' => $this->user['id'],
        'title' => 'Other Content',
        'body' => 'Body',
        'hashtags' => json_encode([]),
        'status' => 'ready',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson("/api/v1/analytics/contents/{$otherContentId}", $this->headers);

    $response->assertStatus(422);
});
