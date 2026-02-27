<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\SyncAdMetricsInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountNotOperationalException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Entities\AdMetricSnapshot;
use App\Domain\PaidAdvertising\Events\AdMetricsSynced;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class SyncAdMetricsUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly AdPlatformFactoryInterface $platformFactory,
        private readonly AdTokenEncryptorInterface $tokenEncryptor,
        private readonly AdMetricSnapshotRepositoryInterface $metricsRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(SyncAdMetricsInput $input): void
    {
        $boost = $this->adBoostRepository->findById(Uuid::fromString($input->boostId));

        if ($boost === null) {
            throw new BoostNotFoundException($input->boostId);
        }

        if ($boost->externalIds === null) {
            return;
        }

        $adAccount = $this->adAccountRepository->findById($boost->adAccountId);

        if ($adAccount === null) {
            throw new AdAccountNotFoundException((string) $boost->adAccountId);
        }

        if (! $adAccount->isOperational()) {
            throw new AdAccountNotOperationalException(
                (string) $adAccount->id,
                $adAccount->status->value,
            );
        }

        $adapter = $this->platformFactory->make($adAccount->provider);
        $decryptedToken = $this->tokenEncryptor->decrypt($adAccount->credentials->encryptedAccessToken);

        $now = new DateTimeImmutable;
        $dateFrom = $boost->startedAt?->format('Y-m-d') ?? $boost->createdAt->format('Y-m-d');
        $dateTo = $now->format('Y-m-d');

        $metricsData = $adapter->getMetrics(
            accessToken: $decryptedToken,
            accountId: $adAccount->providerAccountId,
            adId: $boost->externalIds['ad_id'] ?? '',
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        $snapshot = AdMetricSnapshot::create(
            boostId: $boost->id,
            period: MetricPeriod::Daily,
            impressions: $metricsData['impressions'],
            reach: $metricsData['reach'],
            clicks: $metricsData['clicks'],
            spendCents: $metricsData['spend_cents'],
            spendCurrency: $boost->budget->currency,
            conversions: $metricsData['conversions'],
        );

        $this->metricsRepository->create($snapshot);

        $this->eventDispatcher->dispatch(new AdMetricsSynced(
            aggregateId: (string) $boost->id,
            organizationId: (string) $boost->organizationId,
            userId: (string) $boost->createdBy,
            boostId: (string) $boost->id,
            period: MetricPeriod::Daily->value,
            impressions: $metricsData['impressions'],
            clicks: $metricsData['clicks'],
            spendCents: $metricsData['spend_cents'],
        ));
    }
}
