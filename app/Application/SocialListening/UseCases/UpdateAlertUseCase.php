<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\ListeningAlertOutput;
use App\Application\SocialListening\DTOs\UpdateAlertInput;
use App\Application\SocialListening\Exceptions\ListeningAlertNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;

final class UpdateAlertUseCase
{
    public function __construct(
        private readonly ListeningAlertRepositoryInterface $alertRepository,
    ) {}

    public function execute(UpdateAlertInput $input): ListeningAlertOutput
    {
        $alertId = Uuid::fromString($input->alertId);
        $organizationId = Uuid::fromString($input->organizationId);

        $alert = $this->alertRepository->findById($alertId);

        if ($alert === null || (string) $alert->organizationId !== (string) $organizationId) {
            throw new ListeningAlertNotFoundException();
        }

        $condition = null;
        if ($input->conditionType !== null && $input->threshold !== null && $input->windowMinutes !== null) {
            $condition = AlertCondition::create(
                type: ConditionType::from($input->conditionType),
                threshold: $input->threshold,
                windowMinutes: $input->windowMinutes,
            );
        }

        $channels = null;
        if ($input->channels !== null) {
            $channels = array_map(
                fn (string $ch) => NotificationChannel::from($ch),
                $input->channels,
            );
        }

        $alert = $alert->updateDetails(
            name: $input->name,
            condition: $condition,
            queryIds: $input->queryIds,
            channels: $channels,
            cooldownMinutes: $input->cooldownMinutes,
        );

        $this->alertRepository->update($alert);

        return ListeningAlertOutput::fromEntity($alert);
    }
}
