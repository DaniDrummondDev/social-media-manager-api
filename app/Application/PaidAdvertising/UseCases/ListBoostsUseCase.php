<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\BoostOutput;
use App\Application\PaidAdvertising\DTOs\ListBoostsInput;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListBoostsUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
    ) {}

    /**
     * @return array{items: array<BoostOutput>, next_cursor: ?string}
     */
    public function execute(ListBoostsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->adBoostRepository->findByOrganizationId(
            organizationId: $organizationId,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($boost) => BoostOutput::fromEntity($boost),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
