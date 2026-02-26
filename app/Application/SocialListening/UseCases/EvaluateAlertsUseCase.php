<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\Services\AlertEvaluationService;
use DateTimeImmutable;

final class EvaluateAlertsUseCase
{
    public function __construct(
        private readonly ListeningAlertRepositoryInterface $alertRepository,
        private readonly MentionRepositoryInterface $mentionRepository,
        private readonly AlertEvaluationService $evaluationService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(): int
    {
        $activeAlerts = $this->alertRepository->findAllActive();
        $triggeredCount = 0;

        foreach ($activeAlerts as $alert) {
            foreach ($alert->queryIds as $queryId) {
                $queryUuid = Uuid::fromString($queryId);
                $now = new DateTimeImmutable();

                $windowFrom = $now->modify("-{$alert->condition->windowMinutes} minutes");
                $windowTo = $now;

                $currentCount = $this->mentionRepository->countByQueryInPeriod(
                    queryId: $queryUuid,
                    from: $windowFrom,
                    to: $windowTo,
                );

                $previousFrom = $windowFrom->modify("-{$alert->condition->windowMinutes} minutes");
                $previousTo = $windowFrom;

                $previousCount = $this->mentionRepository->countByQueryInPeriod(
                    queryId: $queryUuid,
                    from: $previousFrom,
                    to: $previousTo,
                );

                $recentMentions = $this->mentionRepository->findByQueryId(
                    queryId: $queryUuid,
                    limit: $currentCount > 0 ? $currentCount : 20,
                );

                $triggered = $this->evaluationService->evaluate(
                    alert: $alert,
                    recentMentions: $recentMentions,
                    previousPeriodCount: $previousCount,
                );

                if ($triggered) {
                    $alert = $alert->markTriggered($queryId);
                    $this->alertRepository->update($alert);

                    $this->eventDispatcher->dispatch(...$alert->domainEvents);

                    $triggeredCount++;

                    break;
                }
            }
        }

        return $triggeredCount;
    }
}
