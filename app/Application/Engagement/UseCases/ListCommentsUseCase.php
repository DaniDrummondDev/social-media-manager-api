<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CommentOutput;
use App\Application\Engagement\DTOs\ListCommentsInput;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListCommentsUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
    ) {}

    /**
     * @return array<CommentOutput>
     */
    public function execute(ListCommentsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $filters = array_filter([
            'provider' => $input->provider,
            'campaign_id' => $input->campaignId,
            'content_id' => $input->contentId,
            'sentiment' => $input->sentiment,
            'is_read' => $input->isRead,
            'is_replied' => $input->isReplied,
            'search' => $input->search,
            'from' => $input->from,
            'to' => $input->to,
        ], fn ($v) => $v !== null);

        $comments = $this->commentRepository->findByOrganizationId(
            $organizationId,
            $filters,
            $input->cursor,
            $input->limit,
        );

        return array_map(
            fn ($comment) => CommentOutput::fromEntity($comment),
            $comments,
        );
    }
}
