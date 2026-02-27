<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdOAuthStateServiceInterface;
use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\AdAccountOutput;
use App\Application\PaidAdvertising\DTOs\HandleAdAccountCallbackInput;
use App\Application\PaidAdvertising\Exceptions\AdOAuthStateInvalidException;
use App\Application\PaidAdvertising\UseCases\HandleAdAccountCallbackUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAdAccountForCallback(string $orgId, string $userId): AdAccount
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

function makeCallbackTokenData(): array
{
    return [
        'access_token' => 'plain-access-token',
        'refresh_token' => 'plain-refresh-token',
        'expires_at' => '+2 hours',
        'account_id' => 'act_123',
        'account_name' => 'Test Account',
        'scopes' => ['ads_read', 'ads_management'],
    ];
}

function makeCallbackAdapter(array $tokenData): AdPlatformInterface
{
    $adapter = Mockery::mock(AdPlatformInterface::class);
    $adapter->shouldReceive('handleCallback')->once()->andReturn($tokenData);

    return $adapter;
}

beforeEach(function () {
    $this->stateService = Mockery::mock(AdOAuthStateServiceInterface::class);
    $this->platformFactory = Mockery::mock(AdPlatformFactoryInterface::class);
    $this->tokenEncryptor = Mockery::mock(AdTokenEncryptorInterface::class);
    $this->adAccountRepository = Mockery::mock(AdAccountRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new HandleAdAccountCallbackUseCase(
        $this->stateService,
        $this->platformFactory,
        $this->tokenEncryptor,
        $this->adAccountRepository,
        $this->eventDispatcher,
    );
});

it('creates new ad account on callback', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $tokenData = makeCallbackTokenData();
    $adapter = makeCallbackAdapter($tokenData);

    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturn([
        'organizationId' => $orgId,
        'userId' => $userId,
        'provider' => 'meta',
    ]);

    $this->platformFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->tokenEncryptor->shouldReceive('encrypt')->twice()->andReturn('encrypted-value');
    $this->adAccountRepository->shouldReceive('findByProviderAndProviderAccountId')->once()->andReturnNull();
    $this->adAccountRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new HandleAdAccountCallbackInput(
        code: 'auth-code',
        state: 'valid-state',
    ));

    expect($output)->toBeInstanceOf(AdAccountOutput::class)
        ->and($output->provider)->toBe('meta')
        ->and($output->providerAccountId)->toBe('act_123')
        ->and($output->providerAccountName)->toBe('Test Account')
        ->and($output->status)->toBe('active');
});

it('throws on invalid state', function () {
    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturnNull();

    $this->useCase->execute(new HandleAdAccountCallbackInput(
        code: 'auth-code',
        state: 'invalid-state',
    ));
})->throws(AdOAuthStateInvalidException::class);

it('updates credentials when existing account belongs to same organization', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $tokenData = makeCallbackTokenData();
    $adapter = makeCallbackAdapter($tokenData);

    $existing = createTestAdAccountForCallback($orgId, $userId);

    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturn([
        'organizationId' => $orgId,
        'userId' => $userId,
        'provider' => 'meta',
    ]);

    $this->platformFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->tokenEncryptor->shouldReceive('encrypt')->twice()->andReturn('new-encrypted-value');
    $this->adAccountRepository->shouldReceive('findByProviderAndProviderAccountId')->once()->andReturn($existing);
    $this->adAccountRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch');

    $output = $this->useCase->execute(new HandleAdAccountCallbackInput(
        code: 'auth-code',
        state: 'valid-state',
    ));

    expect($output)->toBeInstanceOf(AdAccountOutput::class)
        ->and($output->provider)->toBe('meta')
        ->and($output->status)->toBe('active');
});

it('throws when existing account belongs to different organization', function () {
    $differentOrgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $tokenData = makeCallbackTokenData();
    $adapter = makeCallbackAdapter($tokenData);

    $existing = createTestAdAccountForCallback($differentOrgId, $userId);

    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturn([
        'organizationId' => (string) Uuid::generate(), // requesting org is different
        'userId' => $userId,
        'provider' => 'meta',
    ]);

    $this->platformFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->tokenEncryptor->shouldReceive('encrypt')->twice()->andReturn('encrypted-value');
    $this->adAccountRepository->shouldReceive('findByProviderAndProviderAccountId')->once()->andReturn($existing);

    $this->useCase->execute(new HandleAdAccountCallbackInput(
        code: 'auth-code',
        state: 'valid-state',
    ));
})->throws(AdAccountNotFoundException::class);
