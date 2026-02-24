<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Repositories;

use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Media\ValueObjects\UploadStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Media\Models\MediaUploadModel;
use DateTimeImmutable;

final class EloquentMediaUploadRepository implements MediaUploadRepositoryInterface
{
    public function __construct(
        private readonly MediaUploadModel $model,
    ) {}

    public function create(MediaUpload $upload): void
    {
        $this->model->newQuery()->create($this->toArray($upload));
    }

    public function update(MediaUpload $upload): void
    {
        $this->model->newQuery()
            ->where('id', (string) $upload->id)
            ->update($this->toArray($upload));
    }

    public function findById(Uuid $id): ?MediaUpload
    {
        /** @var MediaUploadModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return MediaUpload[]
     */
    public function findExpired(): array
    {
        $now = new DateTimeImmutable;

        $records = $this->model->newQuery()
            ->where('expires_at', '<=', $now->format('Y-m-d H:i:s'))
            ->whereNotIn('status', [
                UploadStatus::Completed->value,
                UploadStatus::Aborted->value,
                UploadStatus::Expired->value,
            ])
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, MediaUploadModel> $records */
        return $records->map(fn (MediaUploadModel $record) => $this->toDomain($record))->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    private function toDomain(MediaUploadModel $model): MediaUpload
    {
        /** @var array<int, string> $s3Parts */
        $s3Parts = $model->getAttribute('s3_parts') ?? [];

        return MediaUpload::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            userId: Uuid::fromString($model->getAttribute('user_id')),
            fileName: $model->getAttribute('file_name'),
            mimeType: MimeType::fromString($model->getAttribute('mime_type')),
            totalBytes: $model->getAttribute('total_bytes'),
            chunkSizeBytes: $model->getAttribute('chunk_size_bytes'),
            totalChunks: $model->getAttribute('total_chunks'),
            receivedChunks: $model->getAttribute('received_chunks') ?? [],
            s3UploadId: $model->getAttribute('s3_upload_id'),
            s3Parts: $s3Parts,
            status: UploadStatus::from($model->getAttribute('status')),
            checksum: $model->getAttribute('checksum'),
            expiresAt: new DateTimeImmutable($model->getAttribute('expires_at')->toDateTimeString()),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(MediaUpload $upload): array
    {
        return [
            'id' => (string) $upload->id,
            'organization_id' => (string) $upload->organizationId,
            'user_id' => (string) $upload->userId,
            'file_name' => $upload->fileName,
            'mime_type' => $upload->mimeType->value,
            'total_bytes' => $upload->totalBytes,
            'chunk_size_bytes' => $upload->chunkSizeBytes,
            'total_chunks' => $upload->totalChunks,
            'received_chunks' => $upload->receivedChunks,
            's3_upload_id' => $upload->s3UploadId,
            's3_parts' => $upload->s3Parts,
            'status' => $upload->status->value,
            'checksum' => $upload->checksum,
            'expires_at' => $upload->expiresAt->format('Y-m-d H:i:s'),
        ];
    }
}
