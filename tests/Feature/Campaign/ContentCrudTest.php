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

    // Create campaign for content tests
    $this->campaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $this->campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->user['id'],
        'name' => 'Content Test Campaign',
        'status' => 'draft',
        'tags' => json_encode([]),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

function insertContent(string $campaignId, string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('contents')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'campaign_id' => $campaignId,
        'created_by' => $userId,
        'title' => 'Test Content '.Str::random(4),
        'body' => null,
        'hashtags' => json_encode([]),
        'status' => 'draft',
        'ai_generation_id' => null,
        'deleted_at' => null,
        'purge_at' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('creates content — 201', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/campaigns/{$this->campaignId}/contents", [
            'title' => 'Mega promoção!',
            'body' => 'Aproveite descontos incríveis',
            'hashtags' => ['promoção', 'desconto'],
            'network_overrides' => [
                [
                    'provider' => 'instagram',
                    'body' => 'IG version of the content',
                    'hashtags' => ['ig', 'promo'],
                ],
            ],
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'content')
        ->assertJsonPath('data.attributes.title', 'Mega promoção!')
        ->assertJsonPath('data.attributes.status', 'draft')
        ->assertJsonPath('data.attributes.network_overrides.0.provider', 'instagram');
});

it('lists contents — 200', function () {
    insertContent($this->campaignId, $this->orgId, $this->user['id'], ['title' => 'Content 1']);
    insertContent($this->campaignId, $this->orgId, $this->user['id'], ['title' => 'Content 2']);

    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/campaigns/{$this->campaignId}/contents");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('shows content — 200', function () {
    $id = insertContent($this->campaignId, $this->orgId, $this->user['id'], ['title' => 'Detail Content']);

    $response = $this->withHeaders($this->headers)->getJson("/api/v1/contents/{$id}");

    $response->assertOk()
        ->assertJsonPath('data.attributes.title', 'Detail Content');
});

it('updates content — 200', function () {
    $id = insertContent($this->campaignId, $this->orgId, $this->user['id'], ['title' => 'Original']);

    $response = $this->withHeaders($this->headers)->putJson("/api/v1/contents/{$id}", [
        'title' => 'Updated Title',
        'hashtags' => ['new'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.title', 'Updated Title')
        ->assertJsonPath('data.attributes.hashtags', ['new']);
});

it('deletes content — 200', function () {
    $id = insertContent($this->campaignId, $this->orgId, $this->user['id']);

    $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/contents/{$id}");

    $response->assertOk()
        ->assertJsonPath('data.message', 'Conteúdo excluído com sucesso.');
});

it('rejects content for non-existent campaign — 404', function () {
    $fakeId = (string) Str::uuid();

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/campaigns/{$fakeId}/contents", [
            'title' => 'Test',
        ]);

    $response->assertStatus(404);
});

it('rejects unauthenticated — 401', function () {
    $response = $this->getJson("/api/v1/campaigns/{$this->campaignId}/contents");

    $response->assertStatus(401);
});

it('isolates content by organization', function () {
    $otherUser = $this->createUserInDb(['email' => 'other-content@example.com']);
    $otherOrg = $this->createOrgWithOwner($otherUser['id'], ['name' => 'Other Org', 'slug' => 'other-content']);

    // Create campaign in other org
    $otherCampaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $otherCampaignId,
        'organization_id' => $otherOrg['org']['id'],
        'created_by' => $otherUser['id'],
        'name' => 'Other Campaign',
        'status' => 'draft',
        'tags' => json_encode([]),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $contentId = insertContent($otherCampaignId, $otherOrg['org']['id'], $otherUser['id']);

    // Try to access from current user's org - should return 404 (not found in tenant scope)
    $response = $this->withHeaders($this->headers)->getJson("/api/v1/contents/{$contentId}");

    $response->assertStatus(404);
});
