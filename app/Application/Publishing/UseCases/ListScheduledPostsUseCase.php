<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\DTOs\ListScheduledPostsInput;
use App\Application\Publishing\DTOs\ScheduledPostListOutput;
use App\Application\Publishing\DTOs\ScheduledPostOutput;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class ListScheduledPostsUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
    ) {}

    public function execute(ListScheduledPostsInput $input): ScheduledPostListOutput
    {
        $posts = $this->scheduledPostRepository->findByOrganizationId(
            organizationId: Uuid::fromString($input->organizationId),
            status: $input->status,
            provider: $input->provider,
            campaignId: $input->campaignId,
            from: $input->from !== null ? new DateTimeImmutable($input->from) : null,
            to: $input->to !== null ? new DateTimeImmutable($input->to) : null,
        );

        $items = array_map(
            fn ($post) => ScheduledPostOutput::fromEntity($post),
            $posts,
        );

        return new ScheduledPostListOutput(items: $items);
    }
}
