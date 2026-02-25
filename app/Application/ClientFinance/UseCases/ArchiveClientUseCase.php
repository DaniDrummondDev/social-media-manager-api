<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\Exceptions\ClientNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ArchiveClientUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(string $clientId, string $organizationId, string $userId): void
    {
        $client = $this->clientRepository->findByIdAndOrganization(
            Uuid::fromString($clientId),
            Uuid::fromString($organizationId),
        );

        if ($client === null) {
            throw new ClientNotFoundException();
        }

        $client = $client->archive($userId);

        $this->clientRepository->update($client);
    }
}
