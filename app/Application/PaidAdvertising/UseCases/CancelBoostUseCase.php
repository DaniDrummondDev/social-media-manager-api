<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\BoostOutput;
use App\Application\PaidAdvertising\DTOs\CancelBoostInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CancelBoostUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly AdPlatformFactoryInterface $platformFactory,
        private readonly AdTokenEncryptorInterface $tokenEncryptor,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CancelBoostInput $input): BoostOutput
    {
        $boost = $this->adBoostRepository->findById(Uuid::fromString($input->boostId));

        if ($boost === null) {
            throw new BoostNotFoundException($input->boostId);
        }

        if ((string) $boost->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        $cancelled = $boost->cancel($input->userId);

        if ($boost->externalIds !== null) {
            try {
                $adAccount = $this->adAccountRepository->findById($boost->adAccountId);

                if ($adAccount !== null) {
                    $adapter = $this->platformFactory->make($adAccount->provider);
                    $decryptedToken = $this->tokenEncryptor->decrypt($adAccount->credentials->encryptedAccessToken);

                    $adapter->deleteAd(
                        $decryptedToken,
                        $adAccount->providerAccountId,
                        $boost->externalIds['ad_id'] ?? '',
                    );
                }
            } catch (\Throwable) {
                // Best-effort platform deletion — do not block cancellation
            }
        }

        $this->adBoostRepository->update($cancelled);
        $this->eventDispatcher->dispatch(...$cancelled->domainEvents);

        return BoostOutput::fromEntity($cancelled);
    }
}
