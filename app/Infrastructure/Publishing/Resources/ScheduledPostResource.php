<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Resources;

use App\Application\Publishing\DTOs\ScheduledPostOutput;

final readonly class ScheduledPostResource
{
    /**
     * @param  array{code: string, message: string, is_permanent: bool}|null  $lastError
     */
    private function __construct(
        private string $id,
        private string $contentId,
        private string $socialAccountId,
        private string $provider,
        private string $username,
        private ?string $scheduledAt,
        private string $status,
        private ?string $publishedAt,
        private ?string $externalPostId,
        private ?string $externalPostUrl,
        private int $attempts,
        private int $maxAttempts,
        private ?array $lastError,
        private ?string $nextRetryAt,
        private ?string $contentTitle,
        private ?string $campaignName,
        private string $createdAt,
    ) {}

    public static function fromOutput(ScheduledPostOutput $output): self
    {
        return new self(
            id: $output->id,
            contentId: $output->contentId,
            socialAccountId: $output->socialAccountId,
            provider: $output->provider,
            username: $output->username,
            scheduledAt: $output->scheduledAt,
            status: $output->status,
            publishedAt: $output->publishedAt,
            externalPostId: $output->externalPostId,
            externalPostUrl: $output->externalPostUrl,
            attempts: $output->attempts,
            maxAttempts: $output->maxAttempts,
            lastError: $output->lastError,
            nextRetryAt: $output->nextRetryAt,
            contentTitle: $output->contentTitle,
            campaignName: $output->campaignName,
            createdAt: $output->createdAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'scheduled_post',
            'attributes' => [
                'content_id' => $this->contentId,
                'social_account_id' => $this->socialAccountId,
                'provider' => $this->provider,
                'username' => $this->username,
                'scheduled_at' => $this->scheduledAt,
                'status' => $this->status,
                'published_at' => $this->publishedAt,
                'external_post_id' => $this->externalPostId,
                'external_post_url' => $this->externalPostUrl,
                'attempts' => $this->attempts,
                'max_attempts' => $this->maxAttempts,
                'last_error' => $this->lastError,
                'next_retry_at' => $this->nextRetryAt,
                'content_title' => $this->contentTitle,
                'campaign_name' => $this->campaignName,
                'created_at' => $this->createdAt,
            ],
        ];
    }
}
