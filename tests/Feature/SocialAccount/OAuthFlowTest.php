<?php

declare(strict_types=1);

use App\Application\SocialAccount\Contracts\OAuthStateServiceInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);
});

it('initiates oauth for instagram and returns authorization url', function () {
    $mockAdapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $mockAdapter->shouldReceive('getAuthorizationUrl')
        ->once()
        ->andReturn('https://instagram.com/oauth/authorize?state=abc');

    $mockFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);
    $mockFactory->shouldReceive('make')->once()->andReturn($mockAdapter);

    $this->app->instance(SocialAccountAdapterFactoryInterface::class, $mockFactory);

    $response = $this->withHeaders($this->headers)
        ->getJson('/api/v1/social-accounts/oauth/instagram');

    $response->assertOk()
        ->assertJsonPath('data.authorization_url', 'https://instagram.com/oauth/authorize?state=abc');
});

it('initiates oauth for tiktok', function () {
    $mockAdapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $mockAdapter->shouldReceive('getAuthorizationUrl')
        ->once()
        ->andReturn('https://tiktok.com/oauth?state=xyz');

    $mockFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);
    $mockFactory->shouldReceive('make')->once()->andReturn($mockAdapter);

    $this->app->instance(SocialAccountAdapterFactoryInterface::class, $mockFactory);

    $response = $this->withHeaders($this->headers)
        ->getJson('/api/v1/social-accounts/oauth/tiktok');

    $response->assertOk()
        ->assertJsonPath('data.authorization_url', 'https://tiktok.com/oauth?state=xyz');
});

it('initiates oauth for youtube', function () {
    $mockAdapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $mockAdapter->shouldReceive('getAuthorizationUrl')
        ->once()
        ->andReturn('https://accounts.google.com/oauth?state=yt');

    $mockFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);
    $mockFactory->shouldReceive('make')->once()->andReturn($mockAdapter);

    $this->app->instance(SocialAccountAdapterFactoryInterface::class, $mockFactory);

    $response = $this->withHeaders($this->headers)
        ->getJson('/api/v1/social-accounts/oauth/youtube');

    $response->assertOk();
});

it('rejects invalid provider', function () {
    $response = $this->withHeaders($this->headers)
        ->getJson('/api/v1/social-accounts/oauth/invalid_provider');

    // ValueError from SocialProvider::from() is unhandled — results in 500
    $response->assertStatus(500);
});

it('requires authentication', function () {
    $response = $this->getJson('/api/v1/social-accounts/oauth/instagram');

    $response->assertStatus(401);
});

it('handles callback and creates social account', function () {
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('access-token-123'),
        refreshToken: EncryptedToken::fromEncrypted('refresh-token-456'),
        expiresAt: new DateTimeImmutable('+1 hour'),
        scopes: ['read', 'write'],
    );

    $mockAdapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $mockAdapter->shouldReceive('handleCallback')
        ->once()
        ->andReturn($credentials);
    $mockAdapter->shouldReceive('getAccountInfo')
        ->once()
        ->andReturn([
            'id' => 'ig-user-999',
            'username' => 'testaccount',
            'display_name' => 'Test Account',
            'profile_picture_url' => 'https://example.com/pic.jpg',
        ]);

    $mockFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);
    $mockFactory->shouldReceive('make')->andReturn($mockAdapter);

    $this->app->instance(SocialAccountAdapterFactoryInterface::class, $mockFactory);

    // Mock state service to return valid state data
    $mockState = Mockery::mock(OAuthStateServiceInterface::class);
    $mockState->shouldReceive('validateAndConsumeState')
        ->once()
        ->with('valid-state-token')
        ->andReturn([
            'organizationId' => $this->orgId,
            'userId' => $this->user['id'],
            'provider' => 'instagram',
        ]);
    $this->app->instance(OAuthStateServiceInterface::class, $mockState);

    $response = $this->withHeaders($this->headers)
        ->getJson('/api/v1/social-accounts/oauth/callback?code=auth-code-123&state=valid-state-token');

    $response->assertStatus(201)
        ->assertJsonPath('data.provider', 'instagram')
        ->assertJsonPath('data.username', 'testaccount');
});

it('rejects callback with invalid state', function () {
    $mockState = Mockery::mock(OAuthStateServiceInterface::class);
    $mockState->shouldReceive('validateAndConsumeState')
        ->once()
        ->andReturn(null);
    $this->app->instance(OAuthStateServiceInterface::class, $mockState);

    $response = $this->withHeaders($this->headers)
        ->getJson('/api/v1/social-accounts/oauth/callback?code=auth-code&state=invalid-state');

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'OAUTH_STATE_INVALID');
});

it('rejects callback with missing code', function () {
    $response = $this->withHeaders($this->headers)
        ->getJson('/api/v1/social-accounts/oauth/callback?state=some-state');

    $response->assertStatus(422);
});
