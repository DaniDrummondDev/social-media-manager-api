<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\ListMentionsInput;
use App\Application\SocialListening\DTOs\MentionOutput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;

final class ListMentionsUseCase
{
    public function __construct(
        private readonly MentionRepositoryInterface $mentionRepository,
    ) {}

    /**
     * @return array{items: array<MentionOutput>, next_cursor: ?string}
     */
    public function execute(ListMentionsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $filters = [];

        if ($input->queryId !== null) {
            $filters['query_id'] = $input->queryId;
        }

        if ($input->platform !== null) {
            $filters['platform'] = $input->platform;
        }

        if ($input->sentiment !== null) {
            $filters['sentiment'] = $input->sentiment;
        }

        if ($input->isFlagged !== null) {
            $filters['is_flagged'] = $input->isFlagged;
        }

        if ($input->isRead !== null) {
            $filters['is_read'] = $input->isRead;
        }

        if ($input->from !== null) {
            $filters['from'] = $input->from;
        }

        if ($input->to !== null) {
            $filters['to'] = $input->to;
        }

        if ($input->search !== null) {
            $filters['search'] = $input->search;
        }

        $result = $this->mentionRepository->findByOrganizationId(
            organizationId: $organizationId,
            filters: $filters,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($mention) => MentionOutput::fromEntity($mention),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
