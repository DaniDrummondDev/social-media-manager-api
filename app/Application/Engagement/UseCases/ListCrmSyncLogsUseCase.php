<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CrmSyncLogOutput;
use App\Application\Engagement\DTOs\ListCrmSyncLogsInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListCrmSyncLogsUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmSyncLogRepositoryInterface $syncLogRepository,
    ) {}

    /**
     * @return array<CrmSyncLogOutput>
     */
    public function execute(ListCrmSyncLogsInput $input): array
    {
        $connId = Uuid::fromString($input->connectionId);
        $connection = $this->connectionRepository->findById($connId);

        if ($connection === null || (string) $connection->organizationId !== $input->organizationId) {
            throw new CrmConnectionNotFoundException($input->connectionId);
        }

        $logs = $this->syncLogRepository->findByConnectionId(
            $connId,
            $input->cursor,
            $input->limit,
        );

        return array_map(
            fn ($log) => CrmSyncLogOutput::fromEntity($log),
            $logs,
        );
    }
}
