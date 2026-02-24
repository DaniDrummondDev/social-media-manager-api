<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Repositories;

use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Media\ValueObjects\ScanStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Media\Models\MediaModel;
use DateTimeImmutable;

final class EloquentMediaRepository implements MediaRepositoryInterface
{
    public function __construct(
        private readonly MediaModel $model,
    ) {}

    public function create(Media $media): void
    {
        $this->model->newQuery()->create($this->toArray($media));
    }

    public function update(Media $media): void
    {
        $this->model->newQuery()
            ->where('id', (string) $media->id)
            ->update($this->toArray($media));
    }

    public function findById(Uuid $id): ?Media
    {
        /** @var MediaModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return Media[]
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, MediaModel> $records */
        return $records->map(fn (MediaModel $record) => $this->toDomain($record))->all();
    }

    public function findByChecksum(Uuid $organizationId, string $checksum): ?Media
    {
        /** @var MediaModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('checksum', $checksum)
            ->whereNull('deleted_at')
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    /**
     * @return Media[]
     */
    public function findPurgeable(): array
    {
        $now = new DateTimeImmutable;

        $records = $this->model->newQuery()
            ->whereNotNull('deleted_at')
            ->whereNotNull('purge_at')
            ->where('purge_at', '<=', $now->format('Y-m-d H:i:s'))
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, MediaModel> $records */
        return $records->map(fn (MediaModel $record) => $this->toDomain($record))->all();
    }

    private function toDomain(MediaModel $model): Media
    {
        $width = $model->getAttribute('width');
        $height = $model->getAttribute('height');
        $dimensions = ($width !== null && $height !== null)
            ? Dimensions::create($width, $height)
            : null;

        return Media::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            uploadedBy: Uuid::fromString($model->getAttribute('uploaded_by')),
            fileName: $model->getAttribute('file_name'),
            originalName: $model->getAttribute('original_name'),
            mimeType: MimeType::fromString($model->getAttribute('mime_type')),
            fileSize: FileSize::fromBytes($model->getAttribute('file_size')),
            dimensions: $dimensions,
            durationSeconds: $model->getAttribute('duration_seconds'),
            storagePath: $model->getAttribute('storage_path'),
            thumbnailPath: $model->getAttribute('thumbnail_path'),
            disk: $model->getAttribute('disk'),
            checksum: $model->getAttribute('checksum'),
            scanStatus: ScanStatus::from($model->getAttribute('scan_status')),
            scannedAt: $model->getAttribute('scanned_at')
                ? new DateTimeImmutable($model->getAttribute('scanned_at')->toDateTimeString())
                : null,
            compatibility: null,
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
            deletedAt: $model->getAttribute('deleted_at')
                ? new DateTimeImmutable($model->getAttribute('deleted_at')->toDateTimeString())
                : null,
            purgeAt: $model->getAttribute('purge_at')
                ? new DateTimeImmutable($model->getAttribute('purge_at')->toDateTimeString())
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Media $media): array
    {
        return [
            'id' => (string) $media->id,
            'organization_id' => (string) $media->organizationId,
            'uploaded_by' => (string) $media->uploadedBy,
            'file_name' => $media->fileName,
            'original_name' => $media->originalName,
            'mime_type' => $media->mimeType->value,
            'file_size' => $media->fileSize->bytes,
            'width' => $media->dimensions?->width,
            'height' => $media->dimensions?->height,
            'duration_seconds' => $media->durationSeconds,
            'storage_path' => $media->storagePath,
            'thumbnail_path' => $media->thumbnailPath,
            'disk' => $media->disk,
            'checksum' => $media->checksum,
            'scan_status' => $media->scanStatus->value,
            'scanned_at' => $media->scannedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $media->deletedAt?->format('Y-m-d H:i:s'),
            'purge_at' => $media->purgeAt?->format('Y-m-d H:i:s'),
        ];
    }
}
