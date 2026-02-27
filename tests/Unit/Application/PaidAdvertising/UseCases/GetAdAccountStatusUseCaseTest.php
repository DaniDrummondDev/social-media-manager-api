<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\AdAccountOutput;
use App\Application\PaidAdvertising\DTOs\GetAdAccountStatusInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\UseCases\GetAdAccountStatusUseCase;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAdAccountForStatus(string $orgId): AdAccount
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

    $this->useCase = new GetAdAccountStatusUseCase($this->adAccountRepository);
});

it('returns account output for valid account', function () {
    $orgId = (string) Uuid::generate();
    $account = createTestAdAccountForStatus($orgId);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $output = $this->useCase->execute(new GetAdAccountStatusInput(
        organizationId: $orgId,
        accountId: (string) $account->id,
    ));

    expect($output)->toBeInstanceOf(AdAccountOutput::class)
        ->and($output->provider)->toBe('meta')
        ->and($output->providerAccountName)->toBe('Test Account')
        ->and($output->status)->toBe('active')
        ->and($output->isOperational)->toBeTrue();
});

it('throws when account not found', function () {
    $this->adAccountRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new GetAdAccountStatusInput(
        organizationId: (string) Uuid::generate(),
        accountId: (string) Uuid::generate(),
    ));
})->throws(AdAccountNotFoundException::class);

it('throws when account belongs to different organization', function () {
    $account = createTestAdAccountForStatus((string) Uuid::generate());

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $this->useCase->execute(new GetAdAccountStatusInput(
        organizationId: (string) Uuid::generate(), // different org
        accountId: (string) $account->id,
    ));
})->throws(AdAccountAuthorizationException::class);
