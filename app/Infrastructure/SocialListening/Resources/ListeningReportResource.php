<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Resources;

use App\Application\SocialListening\DTOs\ListeningReportOutput;

final readonly class ListeningReportResource
{
    public function __construct(
        private ListeningReportOutput $output,
    ) {}

    public static function fromOutput(ListeningReportOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'listening_report',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'query_ids' => $this->output->queryIds,
                'period_from' => $this->output->periodFrom,
                'period_to' => $this->output->periodTo,
                'total_mentions' => $this->output->totalMentions,
                'sentiment_breakdown' => $this->output->sentimentBreakdown,
                'top_authors' => $this->output->topAuthors,
                'top_keywords' => $this->output->topKeywords,
                'platform_breakdown' => $this->output->platformBreakdown,
                'status' => $this->output->status,
                'file_path' => $this->output->filePath,
                'generated_at' => $this->output->generatedAt,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
