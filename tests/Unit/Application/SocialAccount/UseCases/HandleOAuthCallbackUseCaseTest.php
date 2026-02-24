<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialAccount\Contracts\OAuthStateServiceInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\HandleOAuthCallbackInput;
use App\Application\SocialAccount\DTOs\SocialAccountOutput;
use App\Application\SocialAccount\Exceptions\OAuthStateInvalidException;
use App\Application\SocialAccount\UseCases\HandleOAuthCallbackUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Exceptions\SocialAccountAlreadyConnectedException;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

function makeTestCredentials(): OAuthCredentials
{
    return OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('encrypted-access'),
        refreshToken: EncryptedToken::fromEncrypted('encrypted-refresh'),
        expiresAt: new DateTimeImmutable('+1 hour'),
        scopes: ['read', 'write'],
    );
}

function makeTestAdapter(OAuthCredentials $credentials): SocialAuthenticatorInterface
{
    $adapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $adapter->shouldReceive('handleCallback')->once()->andReturn($credentials);
    $adapter->shouldReceive('getAccountInfo')->once()->andReturn([
        'id' => 'provider-user-123',
        'username' => 'testuser',
        'display_name' => 'Test User',
        'profile_picture_url' => 'https://example.com/pic.jpg',
    ]);

    return $adapter;
}

beforeEach(function () {
    $this->stateService = Mockery::mock(OAuthStateServiceInterface::class);
    $this->adapterFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);
    $this->socialAccountRepository = Mockery::mock(SocialAccountRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new HandleOAuthCallbackUseCase(
        $this->stateService,
        $this->adapterFactory,
        $this->socialAccountRepository,
        $this->eventDispatcher,
    );
});

it('creates new social account on callback', function () {
    $credentials = makeTestCredentials();
    $adapter = makeTestAdapter($credentials);

    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturn([
        'organizationId' => (string) Uuid::generate(),
        'userId' => (string) Uuid::generate(),
        'provider' => 'instagram',
    ]);

    $this->adapterFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->socialAccountRepository->shouldReceive('findByProviderAndProviderUserId')->once()->andReturnNull();
    $this->socialAccountRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new HandleOAuthCallbackInput(
        code: 'auth-code',
        state: 'valid-state',
    ));

    expect($output)->toBeInstanceOf(SocialAccountOutput::class)
        ->and($output->provider)->toBe('instagram')
        ->and($output->username)->toBe('testuser')
        ->and($output->status)->toBe('connected');
});

it('reconnects existing account for same organization', function () {
    $orgId = Uuid::generate();
    $credentials = makeTestCredentials();
    $adapter = makeTestAdapter($credentials);

    $existingAccount = SocialAccount::create(
        organizationId: $orgId,
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'provider-user-123',
        username: 'olduser',
        credentials: $credentials,
    );
    $disconnected = $existingAccount->disconnect((string) Uuid::generate());

    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturn([
        'organizationId' => (string) $orgId,
        'userId' => (string) Uuid::generate(),
        'provider' => 'instagram',
    ]);

    $this->adapterFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->socialAccountRepository->shouldReceive('findByProviderAndProviderUserId')->once()->andReturn($disconnected);
    $this->socialAccountRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new HandleOAuthCallbackInput(
        code: 'auth-code',
        state: 'valid-state',
    ));

    expect($output)->toBeInstanceOf(SocialAccountOutput::class)
        ->and($output->status)->toBe('connected')
        ->and($output->username)->toBe('testuser');
});

it('throws on invalid state', function () {
    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturnNull();

    $this->useCase->execute(new HandleOAuthCallbackInput(
        code: 'auth-code',
        state: 'invalid-state',
    ));
})->throws(OAuthStateInvalidException::class);

it('throws when account belongs to different organization', function () {
    $credentials = makeTestCredentials();
    $adapter = makeTestAdapter($credentials);

    $existingAccount = SocialAccount::create(
        organizationId: Uuid::generate(), // different org
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'provider-user-123',
        username: 'testuser',
        credentials: $credentials,
    );

    $this->stateService->shouldReceive('validateAndConsumeState')->once()->andReturn([
        'organizationId' => (string) Uuid::generate(), // requesting org
        'userId' => (string) Uuid::generate(),
        'provider' => 'instagram',
    ]);

    $this->adapterFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->socialAccountRepository->shouldReceive('findByProviderAndProviderUserId')->once()->andReturn($existingAccount);

    $this->useCase->execute(new HandleOAuthCallbackInput(
        code: 'auth-code',
        state: 'valid-state',
    ));
})->throws(SocialAccountAlreadyConnectedException::class);
