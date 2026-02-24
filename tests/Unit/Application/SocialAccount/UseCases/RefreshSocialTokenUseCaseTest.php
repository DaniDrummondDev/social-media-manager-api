<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\RefreshSocialTokenInput;
use App\Application\SocialAccount\DTOs\SocialAccountOutput;
use App\Application\SocialAccount\Exceptions\SocialAccountNotFoundException;
use App\Application\SocialAccount\UseCases\RefreshSocialTokenUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

beforeEach(function () {
    $this->socialAccountRepository = Mockery::mock(SocialAccountRepositoryInterface::class);
    $this->adapterFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new RefreshSocialTokenUseCase(
        $this->socialAccountRepository,
        $this->adapterFactory,
        $this->eventDispatcher,
    );
});

it('refreshes token successfully', function () {
    $orgId = Uuid::generate();
    $account = SocialAccount::create(
        organizationId: $orgId,
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-123',
        username: 'myaccount',
        credentials: OAuthCredentials::create(
            EncryptedToken::fromEncrypted('old-token'),
            EncryptedToken::fromEncrypted('old-refresh'),
            new DateTimeImmutable('+1 hour'),
            ['read'],
        ),
    );

    $newCredentials = OAuthCredentials::create(
        EncryptedToken::fromEncrypted('new-token'),
        EncryptedToken::fromEncrypted('new-refresh'),
        new DateTimeImmutable('+2 hours'),
        ['read'],
    );

    $adapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $adapter->shouldReceive('refreshToken')->once()->andReturn($newCredentials);
    $this->adapterFactory->shouldReceive('make')->once()->andReturn($adapter);

    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->socialAccountRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new RefreshSocialTokenInput(
        organizationId: (string) $orgId,
        accountId: (string) $account->id,
    ));

    expect($output)->toBeInstanceOf(SocialAccountOutput::class)
        ->and($output->status)->toBe('connected');
});

it('marks token expired on refresh failure', function () {
    $orgId = Uuid::generate();
    $account = SocialAccount::create(
        organizationId: $orgId,
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-123',
        username: 'myaccount',
        credentials: OAuthCredentials::create(
            EncryptedToken::fromEncrypted('token'), null, null, [],
        ),
    );

    $adapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $adapter->shouldReceive('refreshToken')->once()->andThrow(new RuntimeException('Provider error'));
    $this->adapterFactory->shouldReceive('make')->once()->andReturn($adapter);

    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->socialAccountRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new RefreshSocialTokenInput(
        organizationId: (string) $orgId,
        accountId: (string) $account->id,
    ));

    expect($output->status)->toBe('token_expired');
});

it('throws when account not found', function () {
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new RefreshSocialTokenInput(
        organizationId: (string) Uuid::generate(),
        accountId: (string) Uuid::generate(),
    ));
})->throws(SocialAccountNotFoundException::class);
