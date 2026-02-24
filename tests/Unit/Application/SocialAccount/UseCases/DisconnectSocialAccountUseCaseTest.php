<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\DisconnectSocialAccountInput;
use App\Application\SocialAccount\Exceptions\SocialAccountAuthorizationException;
use App\Application\SocialAccount\Exceptions\SocialAccountNotFoundException;
use App\Application\SocialAccount\UseCases\DisconnectSocialAccountUseCase;
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

    $this->useCase = new DisconnectSocialAccountUseCase(
        $this->socialAccountRepository,
        $this->adapterFactory,
        $this->eventDispatcher,
    );
});

it('disconnects social account successfully', function () {
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

    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);
    $this->socialAccountRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $adapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $adapter->shouldReceive('revokeToken')->once();
    $this->adapterFactory->shouldReceive('make')->once()->andReturn($adapter);

    $this->useCase->execute(new DisconnectSocialAccountInput(
        organizationId: (string) $orgId,
        userId: (string) Uuid::generate(),
        accountId: (string) $account->id,
    ));
});

it('throws when account not found', function () {
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new DisconnectSocialAccountInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        accountId: (string) Uuid::generate(),
    ));
})->throws(SocialAccountNotFoundException::class);

it('throws when account belongs to different organization', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::TikTok,
        providerUserId: 'tt-456',
        username: 'other',
        credentials: OAuthCredentials::create(
            EncryptedToken::fromEncrypted('token'), null, null, [],
        ),
    );

    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $this->useCase->execute(new DisconnectSocialAccountInput(
        organizationId: (string) Uuid::generate(), // different org
        userId: (string) Uuid::generate(),
        accountId: (string) $account->id,
    ));
})->throws(SocialAccountAuthorizationException::class);
