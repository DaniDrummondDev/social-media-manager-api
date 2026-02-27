<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;

it('creates credentials preserving all fields', function () {
    $expiresAt = new DateTimeImmutable('+2 hours');
    $creds = AdAccountCredentials::create(
        'encrypted-access',
        'encrypted-refresh',
        $expiresAt,
        ['ads_read', 'ads_management'],
    );

    expect($creds->encryptedAccessToken)->toBe('encrypted-access')
        ->and($creds->encryptedRefreshToken)->toBe('encrypted-refresh')
        ->and($creds->expiresAt)->toBe($expiresAt)
        ->and($creds->scopes)->toBe(['ads_read', 'ads_management']);
});

it('detects expired token', function () {
    $expired = AdAccountCredentials::create('token', null, new DateTimeImmutable('-1 hour'));
    $notExpired = AdAccountCredentials::create('token', null, new DateTimeImmutable('+1 hour'));

    expect($expired->isExpired())->toBeTrue()
        ->and($notExpired->isExpired())->toBeFalse();
});

it('returns not expired when expiresAt is null', function () {
    $creds = AdAccountCredentials::create('token', null, null);

    expect($creds->isExpired())->toBeFalse();
});

it('detects token expiring soon', function () {
    $expiringSoon = AdAccountCredentials::create('token', null, new DateTimeImmutable('+30 minutes'));
    $notExpiringSoon = AdAccountCredentials::create('token', null, new DateTimeImmutable('+2 hours'));

    expect($expiringSoon->willExpireSoon(60))->toBeTrue()
        ->and($notExpiringSoon->willExpireSoon(60))->toBeFalse();
});

it('returns not expiring soon when expiresAt is null', function () {
    $creds = AdAccountCredentials::create('token', null, null);

    expect($creds->willExpireSoon())->toBeFalse();
});

it('checks if refresh token exists', function () {
    $with = AdAccountCredentials::create('token', 'refresh', null);
    $without = AdAccountCredentials::create('token', null, null);
    $empty = AdAccountCredentials::create('token', '', null);

    expect($with->hasRefreshToken())->toBeTrue()
        ->and($without->hasRefreshToken())->toBeFalse()
        ->and($empty->hasRefreshToken())->toBeFalse();
});
