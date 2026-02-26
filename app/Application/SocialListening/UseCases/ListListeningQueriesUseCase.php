<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\ListeningQueryOutput;
use App\Application\SocialListening\DTOs\ListListeningQueriesInput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;

final class ListListeningQueriesUseCase
{
    public function __construct(
        private readonly ListeningQueryRepositoryInterface $queryRepository,
    ) {}

    /**
     * @return array{items: array<ListeningQueryOutput>, next_cursor: ?string}
     */
    public function execute(ListListeningQueriesInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $filters = [];

        if ($input->status !== null) {
            $filters['status'] = $input->status;
        }

        $result = $this->queryRepository->findByOrganizationId(
            organizationId: $organizationId,
            filters: $filters,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($query) => ListeningQueryOutput::fromEntity($query),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
