<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Resources;

use App\Application\Analytics\DTOs\ExportReportOutput;

final readonly class ExportResource
{
    private function __construct(
        private ExportReportOutput $output,
    ) {}

    public static function fromOutput(ExportReportOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->exportId,
            'type' => 'report_export',
            'attributes' => [
                'report_type' => $this->output->type,
                'format' => $this->output->format,
                'status' => $this->output->status,
                'download_url' => $this->output->downloadUrl,
                'file_size' => $this->output->fileSize,
                'expires_at' => $this->output->expiresAt,
                'completed_at' => $this->output->completedAt,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
