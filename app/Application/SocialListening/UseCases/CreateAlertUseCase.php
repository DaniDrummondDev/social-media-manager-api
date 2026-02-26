<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\CreateAlertInput;
use App\Application\SocialListening\DTOs\ListeningAlertOutput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;

final class CreateAlertUseCase
{
    public function __construct(
        private readonly ListeningAlertRepositoryInterface $alertRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateAlertInput $input): ListeningAlertOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $condition = AlertCondition::create(
            type: ConditionType::from($input->conditionType),
            threshold: $input->threshold,
            windowMinutes: $input->windowMinutes,
        );

        $channels = array_map(
            fn (string $ch) => NotificationChannel::from($ch),
            $input->channels,
        );

        $alert = ListeningAlert::create(
            organizationId: $organizationId,
            name: $input->name,
            queryIds: $input->queryIds,
            condition: $condition,
            channels: $channels,
            cooldownMinutes: $input->cooldownMinutes,
            userId: $input->userId,
        );

        $this->alertRepository->create($alert);

        $this->eventDispatcher->dispatch(...$alert->domainEvents);

        return ListeningAlertOutput::fromEntity($alert);
    }
}
