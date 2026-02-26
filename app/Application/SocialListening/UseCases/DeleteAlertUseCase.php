<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\DeleteAlertInput;
use App\Application\SocialListening\Exceptions\ListeningAlertNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;

final class DeleteAlertUseCase
{
    public function __construct(
        private readonly ListeningAlertRepositoryInterface $alertRepository,
    ) {}

    public function execute(DeleteAlertInput $input): void
    {
        $alertId = Uuid::fromString($input->alertId);
        $organizationId = Uuid::fromString($input->organizationId);

        $alert = $this->alertRepository->findById($alertId);

        if ($alert === null || (string) $alert->organizationId !== (string) $organizationId) {
            throw new ListeningAlertNotFoundException();
        }

        $this->alertRepository->delete($alertId);
    }
}
