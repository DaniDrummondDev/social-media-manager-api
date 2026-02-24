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

function insertMedia(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('media')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'uploaded_by' => $userId,
        'file_name' => 'test-'.Str::random(6).'.jpg',
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 2 * 1024 * 1024,
        'width' => 1080,
        'height' => 1080,
        'duration_seconds' => null,
        'storage_path' => "orgs/{$orgId}/media/test.jpg",
        'thumbnail_path' => null,
        'disk' => 'spaces',
        'checksum' => hash('sha256', Str::random(32)),
        'scan_status' => 'pending',
        'scanned_at' => null,
        'deleted_at' => null,
        'purge_at' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));

    return $id;
}

it('lists media for organization', function () {
    insertMedia($this->orgId, $this->user['id'], ['original_name' => 'photo1.jpg']);
    insertMedia($this->orgId, $this->user['id'], ['original_name' => 'photo2.jpg']);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/media');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns empty list for org with no media', function () {
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/media');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('deletes media', function () {
    $mediaId = insertMedia($this->orgId, $this->user['id']);

    $response = $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/media/{$mediaId}");

    $response->assertNoContent();

    // Verify it's no longer listed
    $this->withHeaders($this->headers)->getJson('/api/v1/media')
        ->assertJsonCount(0, 'data');
});

it('returns 422 for non-existent media delete', function () {
    $fakeId = (string) Str::uuid();

    $response = $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/media/{$fakeId}");

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'MEDIA_NOT_FOUND');
});

it('prevents listing media from other org', function () {
    $otherUser = $this->createUserInDb(['email' => 'other-list@example.com']);
    $otherOrgData = $this->createOrgWithOwner($otherUser['id'], ['name' => 'Other Org', 'slug' => 'other-list-org']);
    $otherOrgId = $otherOrgData['org']['id'];

    insertMedia($otherOrgId, $otherUser['id']);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/media');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires authentication', function () {
    $response = $this->getJson('/api/v1/media');
    $response->assertStatus(401);

    $response = $this->deleteJson('/api/v1/media/'.Str::uuid());
    $response->assertStatus(401);
});
