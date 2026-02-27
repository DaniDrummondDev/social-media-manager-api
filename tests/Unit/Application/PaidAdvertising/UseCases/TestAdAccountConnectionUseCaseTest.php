<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\TestAdAccountConnectionInput;
use App\Application\PaidAdvertising\DTOs\TestAdAccountConnectionOutput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\UseCases\TestAdAccountConnectionUseCase;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAdAccountForConnectionTest(string $orgId): AdAccount
{
    return AdAccount::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::generate(),
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
    $this->platformFactory = Mockery::mock(AdPlatformFactoryInterface::class);
    $this->tokenEncryptor = Mockery::mock(AdTokenEncryptorInterface::class);

    $this->useCase = new TestAdAccountConnectionUseCase(
        $this->adAccountRepository,
        $this->platformFactory,
        $this->tokenEncryptor,
    );
});

it('returns connected status when adapter call succeeds', function () {
    $orgId = (string) Uuid::generate();
    $account = createTestAdAccountForConnectionTest($orgId);

    $adapter = Mockery::mock(AdPlatformInterface::class);
    $adapter->shouldReceive('searchInterests')->once()->andReturn([]);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->platformFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->tokenEncryptor->shouldReceive('decrypt')->once()->andReturn('plain-token');

    $output = $this->useCase->execute(new TestAdAccountConnectionInput(
        organizationId: $orgId,
        accountId: (string) $account->id,
    ));

    expect($output)->toBeInstanceOf(TestAdAccountConnectionOutput::class)
        ->and($output->isConnected)->toBeTrue()
        ->and($output->providerAccountName)->toBe('Test Account')
        ->and($output->error)->toBeNull();
});

it('throws when account not found', function () {
    $this->adAccountRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new TestAdAccountConnectionInput(
        organizationId: (string) Uuid::generate(),
        accountId: (string) Uuid::generate(),
    ));
})->throws(AdAccountNotFoundException::class);

it('throws when account belongs to different organization', function () {
    $account = createTestAdAccountForConnectionTest((string) Uuid::generate());

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $this->useCase->execute(new TestAdAccountConnectionInput(
        organizationId: (string) Uuid::generate(), // different org
        accountId: (string) $account->id,
    ));
})->throws(AdAccountAuthorizationException::class);
