<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Repositories;

use App\Domain\Analytics\Entities\ReportExport;
use App\Domain\Analytics\Repositories\ReportExportRepositoryInterface;
use App\Domain\Analytics\ValueObjects\ExportFormat;
use App\Domain\Analytics\ValueObjects\ExportStatus;
use App\Domain\Analytics\ValueObjects\ReportType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Analytics\Models\ReportExportModel;
use DateTimeImmutable;

final class EloquentReportExportRepository implements ReportExportRepositoryInterface
{
    public function __construct(
        private readonly ReportExportModel $model,
    ) {}

    public function create(ReportExport $export): void
    {
        $this->model->newQuery()->create($this->toArray($export));
    }

    public function update(ReportExport $export): void
    {
        $this->model->newQuery()
            ->where('id', (string) $export->id)
            ->update($this->toArray($export));
    }

    public function findById(Uuid $id): ?ReportExport
    {
        /** @var ReportExportModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<ReportExport>
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->orderByDesc('created_at')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ReportExportModel> $records */
        return $records->map(fn (ReportExportModel $r) => $this->toDomain($r))->all();
    }

    public function countRecentByUser(Uuid $userId, DateTimeImmutable $since): int
    {
        return (int) $this->model->newQuery()
            ->where('user_id', (string) $userId)
            ->where('created_at', '>=', $since->format('Y-m-d H:i:s'))
            ->count();
    }

    /**
     * @return array<ReportExport>
     */
    public function findExpired(DateTimeImmutable $now): array
    {
        $records = $this->model->newQuery()
            ->where('status', ExportStatus::Ready->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now->format('Y-m-d H:i:s'))
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ReportExportModel> $records */
        return $records->map(fn (ReportExportModel $r) => $this->toDomain($r))->all();
    }

    private function toDomain(ReportExportModel $model): ReportExport
    {
        $expiresAt = $model->getAttribute('expires_at');
        $completedAt = $model->getAttribute('completed_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return ReportExport::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            userId: Uuid::fromString($model->getAttribute('user_id')),
            type: ReportType::from($model->getAttribute('type')),
            format: ExportFormat::from($model->getAttribute('format')),
            filters: $model->getAttribute('filters') ?? [],
            status: ExportStatus::from($model->getAttribute('status')),
            filePath: $model->getAttribute('file_path'),
            fileSize: $model->getAttribute('file_size') !== null ? (int) $model->getAttribute('file_size') : null,
            errorMessage: $model->getAttribute('error_message'),
            expiresAt: $expiresAt ? new DateTimeImmutable($expiresAt->toDateTimeString()) : null,
            completedAt: $completedAt ? new DateTimeImmutable($completedAt->toDateTimeString()) : null,
            createdAt: new DateTimeImmutable($createdAt->toDateTimeString()),
            updatedAt: new DateTimeImmutable($updatedAt->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ReportExport $export): array
    {
        return [
            'id' => (string) $export->id,
            'organization_id' => (string) $export->organizationId,
            'user_id' => (string) $export->userId,
            'type' => $export->type->value,
            'format' => $export->format->value,
            'filters' => $export->filters,
            'status' => $export->status->value,
            'file_path' => $export->filePath,
            'file_size' => $export->fileSize,
            'error_message' => $export->errorMessage,
            'expires_at' => $export->expiresAt?->format('Y-m-d H:i:s'),
            'completed_at' => $export->completedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
