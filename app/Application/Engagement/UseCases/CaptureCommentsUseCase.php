<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\SocialEngagementFactoryInterface;
use App\Application\Engagement\DTOs\CaptureCommentsInput;
use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use DateTimeImmutable;

final class CaptureCommentsUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly SocialEngagementFactoryInterface $engagementFactory,
    ) {}

    public function execute(CaptureCommentsInput $input): int
    {
        $socialAccountId = Uuid::fromString($input->socialAccountId);
        $account = $this->socialAccountRepository->findById($socialAccountId);

        if ($account === null) {
            return 0;
        }

        $adapter = $this->engagementFactory->make($account->provider);
        $captured = 0;

        // For each published content, fetch comments
        // This is a simplified implementation - real adapter returns external posts
        $commentsData = $adapter->getComments('', null);

        foreach ($commentsData['comments'] ?? [] as $commentData) {
            $existingFilters = [
                'external_comment_id' => $commentData['id'],
                'social_account_id' => $input->socialAccountId,
            ];

            $existing = $this->commentRepository->findByOrganizationId(
                $account->organizationId,
                $existingFilters,
                null,
                1,
            );

            if ($existing !== []) {
                continue;
            }

            $comment = Comment::create(
                organizationId: $account->organizationId,
                contentId: Uuid::fromString($commentData['content_id'] ?? (string) Uuid::generate()),
                socialAccountId: $socialAccountId,
                provider: $account->provider,
                externalCommentId: $commentData['id'],
                authorName: $commentData['author_name'] ?? 'Unknown',
                authorExternalId: $commentData['author_id'] ?? null,
                authorProfileUrl: $commentData['author_profile_url'] ?? null,
                text: $commentData['text'] ?? '',
                sentiment: isset($commentData['sentiment']) ? SocialProvider::tryFrom($commentData['sentiment']) === null ? null : null : null,
                sentimentScore: null,
                isFromOwner: (bool) ($commentData['is_from_owner'] ?? false),
                commentedAt: new DateTimeImmutable($commentData['created_at'] ?? 'now'),
            );

            $this->commentRepository->create($comment->releaseEvents());
            $captured++;
        }

        return $captured;
    }
}
