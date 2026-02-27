<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Events\AdAccountConnected;
use App\Domain\PaidAdvertising\Events\AdAccountDisconnected;
use App\Domain\PaidAdvertising\Exceptions\InvalidAdStatusTransitionException;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAdAccount(AdAccountStatus $status = AdAccountStatus::Active): AdAccount
{
    $credentials = AdAccountCredentials::create('encrypted-token', 'encrypted-refresh', new DateTimeImmutable('+2 hours'), ['ads_read']);

    if ($status === AdAccountStatus::Active) {
        return AdAccount::create(
            organizationId: Uuid::generate(),
            connectedBy: Uuid::generate(),
            provider: AdProvider::Meta,
            providerAccountId: 'act_123456',
            providerAccountName: 'My Meta Ads',
            credentials: $credentials,
        );
    }

    return AdAccount::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        connectedBy: Uuid::generate(),
        provider: AdProvider::Meta,
        providerAccountId: 'act_123456',
        providerAccountName: 'My Meta Ads',
        credentials: $credentials,
        status: $status,
        metadata: null,
        connectedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('creates account with active status and AdAccountConnected event', function () {
    $account = createTestAdAccount();

    expect($account->status)->toBe(AdAccountStatus::Active)
        ->and($account->provider)->toBe(AdProvider::Meta)
        ->and($account->providerAccountId)->toBe('act_123456')
        ->and($account->domainEvents)->toHaveCount(1)
        ->and($account->domainEvents[0])->toBeInstanceOf(AdAccountConnected::class);
});

it('reconstitutes account without events', function () {
    $account = createTestAdAccount(AdAccountStatus::Suspended);

    expect($account->status)->toBe(AdAccountStatus::Suspended)
        ->and($account->domainEvents)->toBeEmpty();
});

it('disconnects from active status', function () {
    $account = createTestAdAccount();
    $disconnected = $account->disconnect('user-123');

    expect($disconnected->status)->toBe(AdAccountStatus::Disconnected)
        ->and($disconnected->domainEvents)->toHaveCount(2)
        ->and($disconnected->domainEvents[1])->toBeInstanceOf(AdAccountDisconnected::class);
});

it('disconnects from token_expired status', function () {
    $account = createTestAdAccount(AdAccountStatus::TokenExpired);
    $disconnected = $account->disconnect('user-123');

    expect($disconnected->status)->toBe(AdAccountStatus::Disconnected);
});

it('throws when disconnecting already disconnected account', function () {
    $account = createTestAdAccount(AdAccountStatus::Disconnected);
    $account->disconnect('user-123');
})->throws(InvalidAdStatusTransitionException::class);

it('refreshes credentials and updates token', function () {
    $account = createTestAdAccount();
    $newCreds = AdAccountCredentials::create('new-token', 'new-refresh', new DateTimeImmutable('+4 hours'), ['ads_management']);

    $refreshed = $account->refreshCredentials($newCreds);

    expect($refreshed->credentials->encryptedAccessToken)->toBe('new-token')
        ->and($refreshed->status)->toBe(AdAccountStatus::Active);
});

it('refreshes credentials and restores from token_expired to active', function () {
    $account = createTestAdAccount(AdAccountStatus::TokenExpired);
    $newCreds = AdAccountCredentials::create('new-token', null, new DateTimeImmutable('+4 hours'));

    $refreshed = $account->refreshCredentials($newCreds);

    expect($refreshed->status)->toBe(AdAccountStatus::Active)
        ->and($refreshed->credentials->encryptedAccessToken)->toBe('new-token');
});

it('marks token expired from active', function () {
    $account = createTestAdAccount();
    $expired = $account->markTokenExpired();

    expect($expired->status)->toBe(AdAccountStatus::TokenExpired);
});

it('throws when marking token expired from disconnected', function () {
    $account = createTestAdAccount(AdAccountStatus::Disconnected);
    $account->markTokenExpired();
})->throws(InvalidAdStatusTransitionException::class);

it('suspends active account', function () {
    $account = createTestAdAccount();
    $suspended = $account->suspend();

    expect($suspended->status)->toBe(AdAccountStatus::Suspended);
});

it('reactivates suspended account', function () {
    $account = createTestAdAccount(AdAccountStatus::Suspended);
    $reactivated = $account->reactivate();

    expect($reactivated->status)->toBe(AdAccountStatus::Active);
});

it('isOperational returns true only for active', function () {
    $active = createTestAdAccount();
    $suspended = createTestAdAccount(AdAccountStatus::Suspended);

    expect($active->isOperational())->toBeTrue()
        ->and($suspended->isOperational())->toBeFalse();
});

it('releases events returning clean instance', function () {
    $account = createTestAdAccount();

    expect($account->domainEvents)->toHaveCount(1);

    $released = $account->releaseEvents();

    expect($released->domainEvents)->toBeEmpty()
        ->and($released->status)->toBe(AdAccountStatus::Active);
});
