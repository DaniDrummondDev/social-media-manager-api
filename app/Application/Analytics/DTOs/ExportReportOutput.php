<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

use App\Domain\Analytics\Entities\ReportExport;

final readonly class ExportReportOutput
{
    public function __construct(
        public string $exportId,
        public string $type,
        public string $format,
        public string $status,
        public ?string $downloadUrl,
        public ?int $fileSize,
        public ?string $expiresAt,
        public ?string $completedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(ReportExport $export): self
    {
        return new self(
            exportId: (string) $export->id,
            type: $export->type->value,
            format: $export->format->value,
            status: $export->status->value,
            downloadUrl: $export->isDownloadable() ? $export->filePath : null,
            fileSize: $export->fileSize,
            expiresAt: $export->expiresAt?->format('c'),
            completedAt: $export->completedAt?->format('c'),
            createdAt: $export->createdAt->format('c'),
        );
    }
}
