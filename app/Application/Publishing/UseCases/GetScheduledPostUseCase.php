<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\DTOs\GetScheduledPostInput;
use App\Application\Publishing\DTOs\ScheduledPostOutput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetScheduledPostUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
    ) {}

    public function execute(GetScheduledPostInput $input): ScheduledPostOutput
    {
        $post = $this->scheduledPostRepository->findById(
            Uuid::fromString($input->scheduledPostId),
        );

        if ($post === null || (string) $post->organizationId !== $input->organizationId) {
            throw new ScheduledPostNotFoundException($input->scheduledPostId);
        }

        return ScheduledPostOutput::fromEntity($post);
    }
}
