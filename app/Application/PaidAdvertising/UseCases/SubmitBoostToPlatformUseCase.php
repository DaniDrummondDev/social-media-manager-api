<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\SubmitBoostToPlatformInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountNotOperationalException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class SubmitBoostToPlatformUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly AudienceRepositoryInterface $audienceRepository,
        private readonly AdPlatformFactoryInterface $platformFactory,
        private readonly AdTokenEncryptorInterface $tokenEncryptor,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(SubmitBoostToPlatformInput $input): void
    {
        $boost = $this->adBoostRepository->findById(Uuid::fromString($input->boostId));

        if ($boost === null) {
            throw new BoostNotFoundException($input->boostId);
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

        $audience = $this->audienceRepository->findById($boost->audienceId);

        if ($audience === null) {
            throw new AudienceNotFoundException((string) $boost->audienceId);
        }

        $adapter = $this->platformFactory->make($adAccount->provider);
        $decryptedToken = $this->tokenEncryptor->decrypt($adAccount->credentials->encryptedAccessToken);

        $boost = $boost->submitForReview();

        $campaignResult = $adapter->createCampaign(
            accessToken: $decryptedToken,
            accountId: $adAccount->providerAccountId,
            name: "Boost {$boost->id}",
            objective: $boost->objective->value,
        );

        $now = new DateTimeImmutable;
        $endDate = $now->modify("+{$boost->durationDays} days");

        $adSetResult = $adapter->createAdSet(
            accessToken: $decryptedToken,
            accountId: $adAccount->providerAccountId,
            campaignId: $campaignResult['campaign_id'],
            name: "AdSet {$boost->id}",
            budgetCents: $boost->budget->amountCents,
            budgetType: $boost->budget->type->value,
            targeting: $audience->targetingSpec->toArray(),
            startDate: $now->format('Y-m-d'),
            endDate: $endDate->format('Y-m-d'),
        );

        $adResult = $adapter->createAd(
            accessToken: $decryptedToken,
            accountId: $adAccount->providerAccountId,
            adSetId: $adSetResult['adset_id'],
            externalPostId: (string) $boost->scheduledPostId,
            name: "Ad {$boost->id}",
        );

        $externalIds = [
            'campaign_id' => $campaignResult['campaign_id'],
            'adset_id' => $adSetResult['adset_id'],
            'ad_id' => $adResult['ad_id'],
        ];

        $activated = $boost->activate($externalIds, (string) $boost->createdBy);

        $this->adBoostRepository->update($activated);
        $this->eventDispatcher->dispatch(...$activated->domainEvents);
    }
}
