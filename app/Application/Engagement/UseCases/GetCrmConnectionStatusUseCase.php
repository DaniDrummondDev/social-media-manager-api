<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CrmConnectionOutput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetCrmConnectionStatusUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
    ) {}

    public function execute(string $organizationId, string $connectionId): CrmConnectionOutput
    {
        $id = Uuid::fromString($connectionId);
        $connection = $this->connectionRepository->findById($id);

        if ($connection === null || (string) $connection->organizationId !== $organizationId) {
            throw new CrmConnectionNotFoundException($connectionId);
        }

        return CrmConnectionOutput::fromEntity($connection);
    }
}
