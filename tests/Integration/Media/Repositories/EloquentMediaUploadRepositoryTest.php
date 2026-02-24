<?php

declare(strict_types=1);

use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Media\ValueObjects\UploadStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(MediaUploadRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'upload-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'upload-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

function createTestUpload(string $orgId, string $userId): MediaUpload
{
    return MediaUpload::create(
        organizationId: Uuid::fromString($orgId),
        userId: Uuid::fromString($userId),
        fileName: 'video-'.Str::random(6).'.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 25 * 1024 * 1024, // 25MB
        chunkSizeBytes: 5 * 1024 * 1024, // 5MB => 5 chunks
    );
}

it('creates and retrieves by id', function () {
    $upload = createTestUpload($this->orgId, $this->userId);
    $this->repository->create($upload);

    $found = $this->repository->findById($upload->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $upload->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->mimeType->value)->toBe('video/mp4')
        ->and($found->totalBytes)->toBe(25 * 1024 * 1024)
        ->and($found->chunkSizeBytes)->toBe(5 * 1024 * 1024)
        ->and($found->totalChunks)->toBe(5)
        ->and($found->status)->toBe(UploadStatus::Initiated)
        ->and($found->receivedChunks)->toBeEmpty()
        ->and($found->s3Parts)->toBeEmpty();
});

it('returns null for non-existent id', function () {
    $found = $this->repository->findById(Uuid::generate());

    expect($found)->toBeNull();
});

it('updates upload with received chunks', function () {
    $upload = createTestUpload($this->orgId, $this->userId);
    $this->repository->create($upload);

    $withChunk = $upload->receiveChunk(1, 'etag-1');
    $withChunk2 = $withChunk->receiveChunk(3, 'etag-3');
    $this->repository->update($withChunk2);

    $found = $this->repository->findById($upload->id);

    expect($found->status)->toBe(UploadStatus::Uploading)
        ->and($found->receivedChunks)->toBe([1, 3])
        ->and($found->s3Parts)->toHaveKey(1)
        ->and($found->s3Parts)->toHaveKey(3);
});

it('finds expired uploads', function () {
    $upload = createTestUpload($this->orgId, $this->userId);
    $this->repository->create($upload);

    // Force expires_at to past via direct DB update
    DB::table('media_uploads')
        ->where('id', (string) $upload->id)
        ->update(['expires_at' => now()->subHour()->toDateTimeString()]);

    $expired = $this->repository->findExpired();

    expect($expired)->toHaveCount(1)
        ->and((string) $expired[0]->id)->toBe((string) $upload->id);
});

it('excludes terminal status from expired', function () {
    $upload = createTestUpload($this->orgId, $this->userId);
    $this->repository->create($upload);

    // Abort the upload and save
    $aborted = $upload->abort();
    $this->repository->update($aborted);

    // Force expires_at to past
    DB::table('media_uploads')
        ->where('id', (string) $upload->id)
        ->update(['expires_at' => now()->subHour()->toDateTimeString()]);

    $expired = $this->repository->findExpired();

    expect($expired)->toBeEmpty();
});

it('deletes upload', function () {
    $upload = createTestUpload($this->orgId, $this->userId);
    $this->repository->create($upload);

    $this->repository->delete($upload->id);

    $found = $this->repository->findById($upload->id);

    expect($found)->toBeNull();
});
