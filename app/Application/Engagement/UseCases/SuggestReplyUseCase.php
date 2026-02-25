<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\AiSuggestionInterface;
use App\Application\Engagement\DTOs\SuggestReplyInput;
use App\Application\Engagement\DTOs\SuggestReplyOutput;
use App\Application\Engagement\Exceptions\CommentNotFoundException;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class SuggestReplyUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly AiSuggestionInterface $aiSuggestion,
    ) {}

    public function execute(SuggestReplyInput $input): SuggestReplyOutput
    {
        $commentId = Uuid::fromString($input->commentId);
        $comment = $this->commentRepository->findById($commentId);

        if ($comment === null || (string) $comment->organizationId !== $input->organizationId) {
            throw new CommentNotFoundException($input->commentId);
        }

        $suggestions = $this->aiSuggestion->suggestReply($comment->text, '');

        return new SuggestReplyOutput(suggestions: $suggestions);
    }
}
