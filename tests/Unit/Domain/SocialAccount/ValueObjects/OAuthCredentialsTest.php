<?php

declare(strict_types=1);

use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;

it('creates with all fields', function () {
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
        refreshToken: EncryptedToken::fromEncrypted('refresh'),
        expiresAt: new DateTimeImmutable('+1 hour'),
        scopes: ['read', 'write'],
    );

    expect((string) $credentials->accessToken)->toBe('access')
        ->and((string) $credentials->refreshToken)->toBe('refresh')
        ->and($credentials->scopes)->toBe(['read', 'write']);
});

it('detects expired token', function () {
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
        expiresAt: new DateTimeImmutable('-1 hour'),
    );

    expect($credentials->isExpired())->toBeTrue();
});

it('detects non-expired token', function () {
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
        expiresAt: new DateTimeImmutable('+1 hour'),
    );

    expect($credentials->isExpired())->toBeFalse();
});

it('returns not expired when expiresAt is null', function () {
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
    );

    expect($credentials->isExpired())->toBeFalse();
});

it('detects will expire soon', function () {
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
        expiresAt: new DateTimeImmutable('+30 minutes'),
    );

    expect($credentials->willExpireSoon(60))->toBeTrue()
        ->and($credentials->willExpireSoon(15))->toBeFalse();
});

it('checks scope existence', function () {
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
        scopes: ['read', 'publish'],
    );

    expect($credentials->hasScope('read'))->toBeTrue()
        ->and($credentials->hasScope('delete'))->toBeFalse();
});

it('detects refresh token presence', function () {
    $withRefresh = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
        refreshToken: EncryptedToken::fromEncrypted('refresh'),
    );

    $withoutRefresh = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
    );

    $withEmptyRefresh = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access'),
        refreshToken: EncryptedToken::fromEncrypted(''),
    );

    expect($withRefresh->hasRefreshToken())->toBeTrue()
        ->and($withoutRefresh->hasRefreshToken())->toBeFalse()
        ->and($withEmptyRefresh->hasRefreshToken())->toBeFalse();
});
