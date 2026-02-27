<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\SearchInterestsInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\UseCases\SearchInterestsUseCase;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAdAccountForSearch(string $orgId): AdAccount
{
    return AdAccount::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::generate(),
        provider: AdProvider::Meta,
        providerAccountId: 'act_123',
        providerAccountName: 'Test Account',
        credentials: AdAccountCredentials::create('enc-token', null, new DateTimeImmutable('+2 hours'), ['ads_read']),
        status: AdAccountStatus::Active,
        metadata: null,
        connectedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('returns interest search results', function () {
    $orgId = (string) Uuid::generate();
    $account = createTestAdAccountForSearch($orgId);

    $repo = mock(AdAccountRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($account);

    $adapter = mock(AdPlatformInterface::class);
    $adapter->shouldReceive('searchInterests')->once()->andReturn([
        ['id' => '1', 'name' => 'Technology', 'audience_size' => 500000],
        ['id' => '2', 'name' => 'Tech News', 'audience_size' => 120000],
    ]);

    $factory = mock(AdPlatformFactoryInterface::class);
    $factory->shouldReceive('make')->once()->with(AdProvider::Meta)->andReturn($adapter);

    $encryptor = mock(AdTokenEncryptorInterface::class);
    $encryptor->shouldReceive('decrypt')->once()->with('enc-token')->andReturn('plain-token');

    $useCase = new SearchInterestsUseCase($repo, $factory, $encryptor);
    $result = $useCase->execute(new SearchInterestsInput(
        organizationId: $orgId,
        accountId: (string) $account->id,
        query: 'tech',
        limit: 10,
    ));

    expect($result->interests)->toHaveCount(2)
        ->and($result->interests[0]['name'])->toBe('Technology')
        ->and($result->interests[1]['name'])->toBe('Tech News');
});

it('throws when ad account not found', function () {
    $repo = mock(AdAccountRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $factory = mock(AdPlatformFactoryInterface::class);
    $encryptor = mock(AdTokenEncryptorInterface::class);

    $useCase = new SearchInterestsUseCase($repo, $factory, $encryptor);
    $useCase->execute(new SearchInterestsInput(
        organizationId: (string) Uuid::generate(),
        accountId: (string) Uuid::generate(),
        query: 'tech',
    ));
})->throws(AdAccountNotFoundException::class);

it('throws when organization does not own the ad account', function () {
    $orgId = (string) Uuid::generate();
    $differentOrgId = (string) Uuid::generate();
    $account = createTestAdAccountForSearch($orgId);

    $repo = mock(AdAccountRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($account);

    $factory = mock(AdPlatformFactoryInterface::class);
    $encryptor = mock(AdTokenEncryptorInterface::class);

    $useCase = new SearchInterestsUseCase($repo, $factory, $encryptor);
    $useCase->execute(new SearchInterestsInput(
        organizationId: $differentOrgId,
        accountId: (string) $account->id,
        query: 'tech',
    ));
})->throws(AdAccountAuthorizationException::class);
