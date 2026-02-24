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
});

function insertCampaign(string $orgId, string $userId, array $overrides = []): string
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

it('creates campaign — 201', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Black Friday 2026',
        'description' => 'Campanha de Black Friday',
        'tags' => ['black-friday', 'promoção'],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'campaign')
        ->assertJsonPath('data.attributes.name', 'Black Friday 2026')
        ->assertJsonPath('data.attributes.status', 'draft')
        ->assertJsonPath('data.attributes.tags', ['black-friday', 'promoção']);
});

it('lists campaigns — 200', function () {
    insertCampaign($this->orgId, $this->user['id'], ['name' => 'Campaign 1']);
    insertCampaign($this->orgId, $this->user['id'], ['name' => 'Campaign 2']);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/campaigns');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('shows campaign — 200', function () {
    $id = insertCampaign($this->orgId, $this->user['id'], ['name' => 'Show Campaign']);

    $response = $this->withHeaders($this->headers)->getJson("/api/v1/campaigns/{$id}");

    $response->assertOk()
        ->assertJsonPath('data.attributes.name', 'Show Campaign');
});

it('updates campaign — 200', function () {
    $id = insertCampaign($this->orgId, $this->user['id'], ['name' => 'Original Name']);

    $response = $this->withHeaders($this->headers)->putJson("/api/v1/campaigns/{$id}", [
        'name' => 'Updated Name',
        'status' => 'active',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.name', 'Updated Name')
        ->assertJsonPath('data.attributes.status', 'active');
});

it('deletes campaign — 200', function () {
    $id = insertCampaign($this->orgId, $this->user['id']);

    $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/campaigns/{$id}");

    $response->assertOk()
        ->assertJsonPath('data.cancelled_schedules', 0)
        ->assertJsonStructure(['data' => ['purge_at']]);
});

it('duplicates campaign — 201', function () {
    $id = insertCampaign($this->orgId, $this->user['id'], ['name' => 'Original']);

    $response = $this->withHeaders($this->headers)->postJson("/api/v1/campaigns/{$id}/duplicate", [
        'name' => 'Copy of Original',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.attributes.name', 'Copy of Original')
        ->assertJsonPath('data.attributes.status', 'draft');
});

it('restores campaign — 200', function () {
    $id = insertCampaign($this->orgId, $this->user['id'], [
        'deleted_at' => now()->toDateTimeString(),
        'purge_at' => now()->addDays(30)->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->postJson("/api/v1/campaigns/{$id}/restore");

    $response->assertOk()
        ->assertJsonPath('data.message', 'Campanha restaurada com sucesso.');
});

it('rejects duplicate name — 422', function () {
    insertCampaign($this->orgId, $this->user['id'], ['name' => 'Unique Name']);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Unique Name',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'DUPLICATE_CAMPAIGN_NAME');
});

it('rejects unauthenticated — 401', function () {
    $response = $this->getJson('/api/v1/campaigns');

    $response->assertStatus(401);
});

it('isolates campaigns by organization', function () {
    $otherUser = $this->createUserInDb(['email' => 'other-camp@example.com']);
    $otherOrg = $this->createOrgWithOwner($otherUser['id'], ['name' => 'Other', 'slug' => 'other-camp']);
    insertCampaign($otherOrg['org']['id'], $otherUser['id'], ['name' => 'Other Org Campaign']);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/campaigns');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
