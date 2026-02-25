<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\CommentOutput;

final readonly class CommentResource
{
    private function __construct(
        private CommentOutput $output,
    ) {}

    public static function fromOutput(CommentOutput $output): self
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
            'type' => 'comment',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'content_id' => $this->output->contentId,
                'social_account_id' => $this->output->socialAccountId,
                'provider' => $this->output->provider,
                'external_comment_id' => $this->output->externalCommentId,
                'author_name' => $this->output->authorName,
                'author_external_id' => $this->output->authorExternalId,
                'author_profile_url' => $this->output->authorProfileUrl,
                'text' => $this->output->text,
                'sentiment' => $this->output->sentiment,
                'sentiment_score' => $this->output->sentimentScore,
                'is_read' => $this->output->isRead,
                'is_from_owner' => $this->output->isFromOwner,
                'replied_at' => $this->output->repliedAt,
                'replied_by' => $this->output->repliedBy,
                'replied_by_automation' => $this->output->repliedByAutomation,
                'reply_text' => $this->output->replyText,
                'reply_external_id' => $this->output->replyExternalId,
                'commented_at' => $this->output->commentedAt,
                'captured_at' => $this->output->capturedAt,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
