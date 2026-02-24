<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Events\SocialAccountConnected;
use App\Domain\SocialAccount\Events\SocialAccountDisconnected;
use App\Domain\SocialAccount\Events\TokenExpired;
use App\Domain\SocialAccount\Events\TokenRefreshed;
use App\Domain\SocialAccount\Exceptions\SocialAccountNotConnectedException;
use App\Domain\SocialAccount\ValueObjects\ConnectionStatus;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

function makeCredentials(): OAuthCredentials
{
    return OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('enc-access'),
        refreshToken: EncryptedToken::fromEncrypted('enc-refresh'),
        expiresAt: new DateTimeImmutable('+1 hour'),
        scopes: ['read', 'write'],
    );
}

it('creates a social account with connected event', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-12345',
        username: '@testuser',
        credentials: makeCredentials(),
        displayName: 'Test User',
    );

    expect($account->status)->toBe(ConnectionStatus::Connected)
        ->and($account->provider)->toBe(SocialProvider::Instagram)
        ->and($account->username)->toBe('@testuser')
        ->and($account->displayName)->toBe('Test User')
        ->and($account->disconnectedAt)->toBeNull()
        ->and($account->deletedAt)->toBeNull()
        ->and($account->domainEvents)->toHaveCount(1)
        ->and($account->domainEvents[0])->toBeInstanceOf(SocialAccountConnected::class)
        ->and($account->domainEvents[0]->provider)->toBe('instagram')
        ->and($account->domainEvents[0]->username)->toBe('@testuser');
});

it('reconstitutes without events', function () {
    $account = SocialAccount::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::TikTok,
        providerUserId: 'tk-999',
        username: '@tiktokuser',
        displayName: null,
        profilePictureUrl: null,
        credentials: makeCredentials(),
        status: ConnectionStatus::Connected,
        lastSyncedAt: null,
        connectedAt: new DateTimeImmutable,
        disconnectedAt: null,
        metadata: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
    );

    expect($account->domainEvents)->toBeEmpty()
        ->and($account->provider)->toBe(SocialProvider::TikTok);
});

it('disconnects and emits event', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::YouTube,
        providerUserId: 'yt-001',
        username: '@ytchannel',
        credentials: makeCredentials(),
    );

    $userId = (string) Uuid::generate();
    $disconnected = $account->disconnect($userId);

    expect($disconnected->status)->toBe(ConnectionStatus::Disconnected)
        ->and($disconnected->disconnectedAt)->not->toBeNull()
        ->and($disconnected->domainEvents)->toHaveCount(2)
        ->and($disconnected->domainEvents[1])->toBeInstanceOf(SocialAccountDisconnected::class);
});

it('throws when disconnecting already disconnected account', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-001',
        username: '@user',
        credentials: makeCredentials(),
    );

    $disconnected = $account->disconnect('user-id');
    $disconnected->disconnect('user-id');
})->throws(SocialAccountNotConnectedException::class);

it('refreshes token and emits event', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-001',
        username: '@user',
        credentials: makeCredentials(),
    );

    $newCreds = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('new-access'),
        refreshToken: EncryptedToken::fromEncrypted('new-refresh'),
        expiresAt: new DateTimeImmutable('+2 hours'),
        scopes: ['read', 'write'],
    );

    $refreshed = $account->refreshToken($newCreds);

    expect($refreshed->credentials->accessToken->value)->toBe('new-access')
        ->and($refreshed->status)->toBe(ConnectionStatus::Connected)
        ->and($refreshed->domainEvents)->toHaveCount(2)
        ->and($refreshed->domainEvents[1])->toBeInstanceOf(TokenRefreshed::class);
});

it('marks token as expired and emits event', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::TikTok,
        providerUserId: 'tk-001',
        username: '@user',
        credentials: makeCredentials(),
    );

    $expired = $account->markTokenExpired();

    expect($expired->status)->toBe(ConnectionStatus::TokenExpired)
        ->and($expired->domainEvents)->toHaveCount(2)
        ->and($expired->domainEvents[1])->toBeInstanceOf(TokenExpired::class);
});

it('marks as requires reconnection', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::YouTube,
        providerUserId: 'yt-001',
        username: '@user',
        credentials: makeCredentials(),
    );

    $expired = $account->markTokenExpired();
    $requiresReconnection = $expired->markRequiresReconnection();

    expect($requiresReconnection->status)->toBe(ConnectionStatus::RequiresReconnection);
});

it('reconnects with new credentials and emits event', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-001',
        username: '@user',
        credentials: makeCredentials(),
    );

    $expired = $account->markTokenExpired();
    $newCreds = makeCredentials();
    $reconnected = $expired->reconnect($newCreds, 'user-id');

    expect($reconnected->status)->toBe(ConnectionStatus::Connected)
        ->and($reconnected->disconnectedAt)->toBeNull()
        ->and($reconnected->domainEvents)->toHaveCount(3)
        ->and($reconnected->domainEvents[2])->toBeInstanceOf(SocialAccountConnected::class);
});

it('updates profile', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-001',
        username: '@olduser',
        credentials: makeCredentials(),
    );

    $updated = $account->updateProfile(username: '@newuser', displayName: 'New Name');

    expect($updated->username)->toBe('@newuser')
        ->and($updated->displayName)->toBe('New Name');
});

it('releases events', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-001',
        username: '@user',
        credentials: makeCredentials(),
    );

    expect($account->domainEvents)->toHaveCount(1);

    $released = $account->releaseEvents();

    expect($released->domainEvents)->toBeEmpty();
});

it('reports active status correctly', function () {
    $account = SocialAccount::create(
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-001',
        username: '@user',
        credentials: makeCredentials(),
    );

    expect($account->isActive())->toBeTrue()
        ->and($account->isTokenExpired())->toBeFalse();

    $expired = $account->markTokenExpired();

    expect($expired->isActive())->toBeFalse()
        ->and($expired->isTokenExpired())->toBeTrue();
});
