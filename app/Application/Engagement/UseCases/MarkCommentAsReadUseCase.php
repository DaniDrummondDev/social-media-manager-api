<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\MarkAsReadInput;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class MarkCommentAsReadUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
    ) {}

    public function execute(MarkAsReadInput $input): void
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $this->commentRepository->markManyAsRead($organizationId, $input->commentIds);
    }
}
