<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\ListAlertsInput;
use App\Application\SocialListening\DTOs\ListeningAlertOutput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;

final class ListAlertsUseCase
{
    public function __construct(
        private readonly ListeningAlertRepositoryInterface $alertRepository,
    ) {}

    /**
     * @return array{items: array<ListeningAlertOutput>, next_cursor: ?string}
     */
    public function execute(ListAlertsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->alertRepository->findByOrganizationId(
            organizationId: $organizationId,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($alert) => ListeningAlertOutput::fromEntity($alert),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
