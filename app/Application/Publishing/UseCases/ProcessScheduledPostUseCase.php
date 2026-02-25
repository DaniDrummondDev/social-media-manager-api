<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\Contracts\SocialPublisherFactoryInterface;
use App\Application\Publishing\DTOs\ProcessScheduledPostInput;
use App\Application\Publishing\DTOs\ScheduledPostOutput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\ValueObjects\PublishError;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;

final class ProcessScheduledPostUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly SocialPublisherFactoryInterface $publisherFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ProcessScheduledPostInput $input): ScheduledPostOutput
    {
        $post = $this->scheduledPostRepository->findById(
            Uuid::fromString($input->scheduledPostId),
        );

        if ($post === null || $post->status !== PublishingStatus::Dispatched) {
            throw new ScheduledPostNotFoundException($input->scheduledPostId);
        }

        $post = $post->markAsPublishing();
        $this->scheduledPostRepository->update($post);

        $account = $this->socialAccountRepository->findById($post->socialAccountId);
        $content = $this->contentRepository->findById($post->contentId);

        $publisher = $this->publisherFactory->make($account->provider);

        try {
            $result = $publisher->publish([
                'social_account_id' => (string) $post->socialAccountId,
                'content_id' => (string) $post->contentId,
                'content_body' => $content?->body,
                'content_title' => $content?->title,
            ]);

            $post = $post->markAsPublished(
                externalPostId: $result['external_post_id'] ?? '',
                externalPostUrl: $result['external_post_url'] ?? '',
            );

            $this->scheduledPostRepository->update($post);

            $this->tryTransitionContentToPublished($post->contentId);
        } catch (\Throwable $e) {
            $isPermanent = $this->isPermanentError($e);

            $post = $post->markAsFailed(new PublishError(
                code: $this->extractErrorCode($e),
                message: $e->getMessage(),
                isPermanent: $isPermanent,
            ));

            $this->scheduledPostRepository->update($post);
        }

        $this->eventDispatcher->dispatch(...$post->domainEvents);

        return ScheduledPostOutput::fromEntity(
            post: $post,
            provider: $account?->provider->value,
            username: $account?->username,
            contentTitle: $content?->title,
        );
    }

    private function tryTransitionContentToPublished(Uuid $contentId): void
    {
        $allPosts = $this->scheduledPostRepository->findByContentId($contentId);

        foreach ($allPosts as $post) {
            if ($post->status->isActive()) {
                return;
            }
        }

        $content = $this->contentRepository->findById($contentId);

        if ($content !== null && $content->status === ContentStatus::Scheduled) {
            $updatedContent = $content->transitionTo(ContentStatus::Published);
            $this->contentRepository->update($updatedContent);
        }
    }

    private function isPermanentError(\Throwable $e): bool
    {
        $code = $this->extractErrorCode($e);

        return in_array($code, [
            'INVALID_CONTENT',
            'ACCOUNT_SUSPENDED',
            'PERMISSION_DENIED',
            'CONTENT_POLICY_VIOLATION',
        ], true);
    }

    private function extractErrorCode(\Throwable $e): string
    {
        if (method_exists($e, 'getErrorCode')) {
            return $e->getErrorCode();
        }

        return 'PROVIDER_ERROR';
    }
}
