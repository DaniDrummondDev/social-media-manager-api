<?php

declare(strict_types=1);

use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(MediaRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'media-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'media-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

function createTestMedia(string $orgId, string $userId, array $overrides = []): Media
{
    return Media::create(
        organizationId: Uuid::fromString($orgId),
        uploadedBy: Uuid::fromString($userId),
        fileName: $overrides['file_name'] ?? 'test-'.Str::random(6).'.jpg',
        originalName: $overrides['original_name'] ?? 'photo.jpg',
        mimeType: $overrides['mime_type'] ?? MimeType::fromString('image/jpeg'),
        fileSize: $overrides['file_size'] ?? FileSize::fromBytes(2 * 1024 * 1024),
        storagePath: $overrides['storage_path'] ?? 'orgs/'.$orgId.'/media/test.jpg',
        disk: $overrides['disk'] ?? 'spaces',
        checksum: $overrides['checksum'] ?? hash('sha256', Str::random(32)),
        dimensions: $overrides['dimensions'] ?? Dimensions::create(1080, 1080),
        durationSeconds: $overrides['duration_seconds'] ?? null,
    );
}

it('creates and retrieves by id', function () {
    $media = createTestMedia($this->orgId, $this->userId);
    $this->repository->create($media);

    $found = $this->repository->findById($media->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $media->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->originalName)->toBe('photo.jpg')
        ->and($found->mimeType->value)->toBe('image/jpeg')
        ->and($found->fileSize->bytes)->toBe(2 * 1024 * 1024)
        ->and($found->disk)->toBe('spaces');
});

it('returns null for non-existent id', function () {
    $found = $this->repository->findById(Uuid::generate());

    expect($found)->toBeNull();
});

it('updates media fields', function () {
    $media = createTestMedia($this->orgId, $this->userId);
    $this->repository->create($media);

    $updated = $media->setThumbnailPath('thumbs/test-thumb.jpg');
    $this->repository->update($updated);

    $found = $this->repository->findById($media->id);

    expect($found->thumbnailPath)->toBe('thumbs/test-thumb.jpg');
});

it('finds by organization id ordered by created_at desc', function () {
    $media1 = createTestMedia($this->orgId, $this->userId, ['file_name' => 'first.jpg']);
    $this->repository->create($media1);

    // Small delay to ensure different created_at
    usleep(10000);

    $media2 = createTestMedia($this->orgId, $this->userId, ['file_name' => 'second.jpg']);
    $this->repository->create($media2);

    $results = $this->repository->findByOrganizationId(Uuid::fromString($this->orgId));

    expect($results)->toHaveCount(2)
        ->and($results[0]->fileName)->toBe('second.jpg')
        ->and($results[1]->fileName)->toBe('first.jpg');
});

it('finds by checksum', function () {
    $checksum = hash('sha256', 'unique-file-content');
    $media = createTestMedia($this->orgId, $this->userId, ['checksum' => $checksum]);
    $this->repository->create($media);

    $found = $this->repository->findByChecksum(Uuid::fromString($this->orgId), $checksum);

    expect($found)->not->toBeNull()
        ->and($found->checksum)->toBe($checksum);
});

it('soft deletes media', function () {
    $media = createTestMedia($this->orgId, $this->userId);
    $this->repository->create($media);

    $deleted = $media->softDelete(30);
    $this->repository->update($deleted);

    // findByOrganizationId excludes deleted
    $results = $this->repository->findByOrganizationId(Uuid::fromString($this->orgId));

    expect($results)->toBeEmpty();

    // But findById still returns it
    $found = $this->repository->findById($media->id);

    expect($found)->not->toBeNull()
        ->and($found->deletedAt)->not->toBeNull();
});

it('finds purgeable media', function () {
    $media = createTestMedia($this->orgId, $this->userId);
    $this->repository->create($media);

    // Soft delete with 0 grace days so purge_at = now
    $deleted = $media->softDelete(0);
    $this->repository->update($deleted);

    // Small delay to ensure purge_at is in the past
    usleep(10000);

    $purgeable = $this->repository->findPurgeable();

    expect($purgeable)->toHaveCount(1)
        ->and((string) $purgeable[0]->id)->toBe((string) $media->id);
});

it('excludes non-purgeable from findPurgeable', function () {
    $media = createTestMedia($this->orgId, $this->userId);
    $this->repository->create($media);

    // Soft delete with 30 days grace — not purgeable yet
    $deleted = $media->softDelete(30);
    $this->repository->update($deleted);

    $purgeable = $this->repository->findPurgeable();

    expect($purgeable)->toBeEmpty();
});
