<?php

declare(strict_types=1);

use App\Application\Media\Contracts\MediaStorageInterface;
use Illuminate\Http\UploadedFile;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Mock storage to avoid real disk operations
    $mockStorage = Mockery::mock(MediaStorageInterface::class);
    $mockStorage->shouldReceive('store')->andReturnNull();
    $mockStorage->shouldReceive('generatePath')->andReturnUsing(
        fn (string $orgId, string $fileName) => "orgs/{$orgId}/media/{$fileName}",
    );
    $this->app->instance(MediaStorageInterface::class, $mockStorage);
});

it('uploads small image file successfully', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 1080, 1080)->size(2048); // 2MB
    $checksum = hash('sha256', $file->getContent());

    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media', [
            'file' => $file,
            'checksum' => $checksum,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.original_name', 'photo.jpg')
        ->assertJsonPath('data.mime_type', 'image/jpeg');
});

it('uploads small video file successfully', function () {
    // Create a fake file that mimics a video
    $file = UploadedFile::fake()->create('video.mp4', 5120, 'video/mp4'); // 5MB
    $checksum = hash('sha256', $file->getContent());

    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media', [
            'file' => $file,
            'checksum' => $checksum,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.original_name', 'video.mp4');
});

it('rejects file exceeding 10MB', function () {
    $file = UploadedFile::fake()->create('big.jpg', 11264, 'image/jpeg'); // 11MB

    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media', [
            'file' => $file,
            'checksum' => hash('sha256', 'test'),
        ]);

    $response->assertStatus(422);
});

it('rejects missing checksum', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(100);

    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media', [
            'file' => $file,
        ]);

    $response->assertStatus(422);
});

it('requires authentication', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(100);

    $response = $this->postJson('/api/v1/media', [
        'file' => $file,
        'checksum' => hash('sha256', 'test'),
    ]);

    $response->assertStatus(401);
});

it('prevents access from other org', function () {
    // Create other user/org
    $otherUser = $this->createUserInDb(['email' => 'other-media@example.com']);
    $otherOrgData = $this->createOrgWithOwner($otherUser['id'], ['name' => 'Other Org', 'slug' => 'other-media-org']);
    $otherOrgId = $otherOrgData['org']['id'];

    // Upload as other user
    $otherHeaders = $this->authHeaders($otherUser['id'], $otherOrgId, $otherUser['email']);

    $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(100);
    $checksum = hash('sha256', $file->getContent());

    $this->withHeaders($otherHeaders)
        ->postJson('/api/v1/media', ['file' => $file, 'checksum' => $checksum])
        ->assertStatus(201);

    // List from our org — should be empty
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/media');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
