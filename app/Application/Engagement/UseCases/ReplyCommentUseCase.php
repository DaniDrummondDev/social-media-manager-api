<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\SocialEngagementFactoryInterface;
use App\Application\Engagement\DTOs\CommentOutput;
use App\Application\Engagement\DTOs\ReplyCommentInput;
use App\Application\Engagement\Exceptions\CommentNotFoundException;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ReplyCommentUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly SocialEngagementFactoryInterface $engagementFactory,
    ) {}

    public function execute(ReplyCommentInput $input): CommentOutput
    {
        $commentId = Uuid::fromString($input->commentId);
        $comment = $this->commentRepository->findById($commentId);

        if ($comment === null || (string) $comment->organizationId !== $input->organizationId) {
            throw new CommentNotFoundException($input->commentId);
        }

        $adapter = $this->engagementFactory->make($comment->provider);
        $result = $adapter->replyToComment($comment->externalCommentId, $input->text);

        $replyExternalId = $result['id'] ?? null;
        $userId = Uuid::fromString($input->userId);
        $comment = $comment->reply($input->text, $userId, $replyExternalId);

        $this->commentRepository->update($comment->releaseEvents());

        return CommentOutput::fromEntity($comment);
    }
}
