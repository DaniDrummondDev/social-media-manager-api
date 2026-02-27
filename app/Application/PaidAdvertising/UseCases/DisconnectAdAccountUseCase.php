<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\DisconnectAdAccountInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Exceptions\BoostNotAllowedException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DisconnectAdAccountUseCase
{
    public function __construct(
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly AdBoostRepositoryInterface $adBoostRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DisconnectAdAccountInput $input): void
    {
        $account = $this->adAccountRepository->findById(Uuid::fromString($input->accountId));

        if ($account === null) {
            throw new AdAccountNotFoundException($input->accountId);
        }

        if ((string) $account->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        $activeBoosts = $this->adBoostRepository->findActiveByAdAccountId($account->id);

        if ($activeBoosts !== []) {
            throw new BoostNotAllowedException(
                'Cannot disconnect ad account with active boosts. Cancel all boosts first.',
            );
        }

        $disconnected = $account->disconnect($input->userId);

        $this->adAccountRepository->update($disconnected);
        $this->eventDispatcher->dispatch(...$disconnected->domainEvents);
    }
}
