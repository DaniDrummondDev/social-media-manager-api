<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\ListeningReportOutput;
use App\Application\SocialListening\DTOs\ListListeningReportsInput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;

final class ListListeningReportsUseCase
{
    public function __construct(
        private readonly ListeningReportRepositoryInterface $reportRepository,
    ) {}

    /**
     * @return array{items: array<ListeningReportOutput>, next_cursor: ?string}
     */
    public function execute(ListListeningReportsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->reportRepository->findByOrganizationId(
            organizationId: $organizationId,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($report) => ListeningReportOutput::fromEntity($report),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
