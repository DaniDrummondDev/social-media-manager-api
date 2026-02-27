<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\DisconnectAdAccountInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\UseCases\DisconnectAdAccountUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Exceptions\BoostNotAllowedException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAdAccountForDisconnect(string $orgId, string $userId): AdAccount
{
    return AdAccount::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::fromString($userId),
        provider: AdProvider::Meta,
        providerAccountId: 'act_123',
        providerAccountName: 'Test Account',
        credentials: AdAccountCredentials::create('encrypted-token', 'encrypted-refresh', new DateTimeImmutable('+2 hours'), ['ads_read']),
        status: AdAccountStatus::Active,
        metadata: null,
        connectedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

beforeEach(function () {
    $this->adAccountRepository = Mockery::mock(AdAccountRepositoryInterface::class);
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new DisconnectAdAccountUseCase(
        $this->adAccountRepository,
        $this->adBoostRepository,
        $this->eventDispatcher,
    );
});

it('disconnects ad account successfully when no active boosts', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $account = createTestAdAccountForDisconnect($orgId, $userId);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->adBoostRepository->shouldReceive('findActiveByAdAccountId')->once()->andReturn([]);
    $this->adAccountRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new DisconnectAdAccountInput(
        organizationId: $orgId,
        userId: $userId,
        accountId: (string) $account->id,
    ));
});

it('throws when account not found', function () {
    $this->adAccountRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new DisconnectAdAccountInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        accountId: (string) Uuid::generate(),
    ));
})->throws(AdAccountNotFoundException::class);

it('throws when account belongs to different organization', function () {
    $account = createTestAdAccountForDisconnect((string) Uuid::generate(), (string) Uuid::generate());

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $this->useCase->execute(new DisconnectAdAccountInput(
        organizationId: (string) Uuid::generate(), // different org
        userId: (string) Uuid::generate(),
        accountId: (string) $account->id,
    ));
})->throws(AdAccountAuthorizationException::class);

it('throws when active boosts exist', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $account = createTestAdAccountForDisconnect($orgId, $userId);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->adBoostRepository->shouldReceive('findActiveByAdAccountId')->once()->andReturn(['boost-placeholder']);

    $this->useCase->execute(new DisconnectAdAccountInput(
        organizationId: $orgId,
        userId: $userId,
        accountId: (string) $account->id,
    ));
})->throws(BoostNotAllowedException::class);
