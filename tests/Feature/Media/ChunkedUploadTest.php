<?php

declare(strict_types=1);

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Mock chunked storage
    $mockChunked = Mockery::mock(ChunkedStorageInterface::class);
    $mockChunked->shouldReceive('initiate')->andReturn('s3-upload-id-123');
    $mockChunked->shouldReceive('uploadPart')->andReturnUsing(
        fn (string $s3UploadId, string $key, int $partNumber, string $data) => "etag-{$partNumber}",
    );
    $mockChunked->shouldReceive('complete')->andReturnUsing(
        fn (string $s3UploadId, string $key, array $parts) => $key,
    );
    $mockChunked->shouldReceive('abort')->andReturnNull();
    $this->app->instance(ChunkedStorageInterface::class, $mockChunked);

    // Mock media storage
    $mockStorage = Mockery::mock(MediaStorageInterface::class);
    $mockStorage->shouldReceive('store')->andReturnNull();
    $mockStorage->shouldReceive('generatePath')->andReturnUsing(
        fn (string $orgId, string $fileName) => "orgs/{$orgId}/media/{$fileName}",
    );
    $this->app->instance(MediaStorageInterface::class, $mockStorage);
});

it('initiates chunked upload', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media/uploads', [
            'file_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'total_bytes' => 25 * 1024 * 1024, // 25MB
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.total_chunks', 5)
        ->assertJsonPath('data.s3_upload_id', 's3-upload-id-123');
});

it('rejects initiate with invalid mime type', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media/uploads', [
            'file_name' => 'file.exe',
            'mime_type' => 'application/x-executable',
            'total_bytes' => 25 * 1024 * 1024,
        ]);

    $response->assertStatus(422);
});

it('uploads chunk and receives confirmation', function () {
    // Initiate first
    $initResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media/uploads', [
            'file_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'total_bytes' => 25 * 1024 * 1024,
        ]);

    $uploadId = $initResponse->json('data.upload_id');

    // Upload chunk
    $response = $this->withHeaders($this->headers)
        ->patchJson("/api/v1/media/uploads/{$uploadId}", [
            'chunk_index' => 1,
            'data' => base64_encode(str_repeat('x', 1024)),
        ]);

    $response->assertOk()
        ->assertJsonPath('data.chunk_index', 1)
        ->assertJsonPath('data.received_count', 1)
        ->assertJsonPath('data.total_chunks', 5);
});

it('gets upload status with progress', function () {
    // Initiate
    $initResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media/uploads', [
            'file_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'total_bytes' => 10 * 1024 * 1024, // 2 chunks
            'chunk_size_bytes' => 5 * 1024 * 1024,
        ]);

    $uploadId = $initResponse->json('data.upload_id');

    // Upload 1 chunk
    $this->withHeaders($this->headers)
        ->patchJson("/api/v1/media/uploads/{$uploadId}", [
            'chunk_index' => 1,
            'data' => base64_encode('chunk-data'),
        ]);

    // Check status
    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/media/uploads/{$uploadId}");

    $response->assertOk()
        ->assertJsonPath('data.status', 'uploading');
});

it('completes upload after all chunks', function () {
    // Initiate with 2 chunks
    $initResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media/uploads', [
            'file_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'total_bytes' => 10 * 1024 * 1024,
            'chunk_size_bytes' => 5 * 1024 * 1024,
        ]);

    $uploadId = $initResponse->json('data.upload_id');

    // Upload both chunks
    $this->withHeaders($this->headers)
        ->patchJson("/api/v1/media/uploads/{$uploadId}", [
            'chunk_index' => 1,
            'data' => base64_encode('chunk-1'),
        ]);

    $this->withHeaders($this->headers)
        ->patchJson("/api/v1/media/uploads/{$uploadId}", [
            'chunk_index' => 2,
            'data' => base64_encode('chunk-2'),
        ]);

    // Complete
    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/media/uploads/{$uploadId}/complete", [
            'checksum' => hash('sha256', 'video-content'),
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.original_name', 'video.mp4');
});

it('aborts upload', function () {
    $initResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/media/uploads', [
            'file_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'total_bytes' => 25 * 1024 * 1024,
        ]);

    $uploadId = $initResponse->json('data.upload_id');

    $response = $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/media/uploads/{$uploadId}");

    $response->assertNoContent();
});

it('requires authentication', function () {
    $response = $this->postJson('/api/v1/media/uploads', [
        'file_name' => 'video.mp4',
        'mime_type' => 'video/mp4',
        'total_bytes' => 25 * 1024 * 1024,
    ]);

    $response->assertStatus(401);
});
