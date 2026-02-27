<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\AdAccountOutput;
use App\Application\PaidAdvertising\DTOs\ListAdAccountsInput;
use App\Application\PaidAdvertising\UseCases\ListAdAccountsUseCase;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAdAccountForList(string $orgId, AdProvider $provider = AdProvider::Meta): AdAccount
{
    return AdAccount::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::generate(),
        provider: $provider,
        providerAccountId: 'act_' . random_int(100, 999),
        providerAccountName: 'Test Account ' . $provider->value,
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

    $this->useCase = new ListAdAccountsUseCase($this->adAccountRepository);
});

it('lists all accounts for organization', function () {
    $orgId = (string) Uuid::generate();
    $account1 = createTestAdAccountForList($orgId, AdProvider::Meta);
    $account2 = createTestAdAccountForList($orgId, AdProvider::TikTok);

    $this->adAccountRepository->shouldReceive('findByOrganizationId')->once()->andReturn([$account1, $account2]);

    $output = $this->useCase->execute(new ListAdAccountsInput(
        organizationId: $orgId,
    ));

    expect($output)->toBeArray()
        ->and($output)->toHaveCount(2)
        ->and($output[0])->toBeInstanceOf(AdAccountOutput::class)
        ->and($output[0]->provider)->toBe('meta')
        ->and($output[1]->provider)->toBe('tiktok');
});

it('filters by provider when specified', function () {
    $orgId = (string) Uuid::generate();
    $account = createTestAdAccountForList($orgId, AdProvider::Google);

    $this->adAccountRepository->shouldReceive('findByOrganizationAndProvider')->once()->andReturn([$account]);

    $output = $this->useCase->execute(new ListAdAccountsInput(
        organizationId: $orgId,
        provider: 'google',
    ));

    expect($output)->toHaveCount(1)
        ->and($output[0]->provider)->toBe('google');
});

it('returns empty array when no accounts exist', function () {
    $this->adAccountRepository->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $output = $this->useCase->execute(new ListAdAccountsInput(
        organizationId: (string) Uuid::generate(),
    ));

    expect($output)->toBeArray()
        ->and($output)->toBeEmpty();
});
