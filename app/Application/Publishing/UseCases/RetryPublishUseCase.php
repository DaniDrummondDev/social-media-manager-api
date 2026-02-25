<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\DTOs\RetryPublishInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class RetryPublishUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return array{id: string, status: string, message: string}
     */
    public function execute(RetryPublishInput $input): array
    {
        $post = $this->scheduledPostRepository->findById(
            Uuid::fromString($input->scheduledPostId),
        );

        if ($post === null || (string) $post->organizationId !== $input->organizationId) {
            throw new ScheduledPostNotFoundException($input->scheduledPostId);
        }

        $retried = $post->retryNow();

        $this->scheduledPostRepository->update($retried);
        $this->eventDispatcher->dispatch(...$retried->domainEvents);

        return [
            'id' => (string) $retried->id,
            'status' => $retried->status->value,
            'message' => 'Publicação reenviada para processamento.',
        ];
    }
}
