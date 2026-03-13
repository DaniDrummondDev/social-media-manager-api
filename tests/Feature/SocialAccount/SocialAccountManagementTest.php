<?php

declare(strict_types=1);

use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Stub adapter factory to avoid missing binding errors
    $mockAdapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $mockAdapter->shouldReceive('revokeToken')->andReturnNull();
    $mockAdapter->shouldReceive('refreshToken')->andReturn(
        OAuthCredentials::create(
            accessToken: EncryptedToken::fromEncrypted('new-access-token'),
            refreshToken: EncryptedToken::fromEncrypted('new-refresh-token'),
            expiresAt: new DateTimeImmutable('+1 hour'),
            scopes: [],
        ),
    );
    $mockFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);
    $mockFactory->shouldReceive('make')->andReturn($mockAdapter);
    $this->app->instance(SocialAccountAdapterFactoryInterface::class, $mockFactory);
});

function insertSocialAccount(string $orgId, string $userId, array $overrides = []): string
{
    $id = $overrides['id'] ?? (string) Str::uuid();

    DB::table('social_accounts')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'connected_by' => $userId,
        'provider' => 'instagram',
        'provider_user_id' => 'ig-'.Str::random(6),
        'username' => 'user_'.Str::random(4),
        'display_name' => 'Test Account',
        'profile_picture_url' => null,
        'access_token' => 'encrypted-access-token',
        'refresh_token' => 'encrypted-refresh-token',
        'token_expires_at' => now()->addHour()->toDateTimeString(),
        'scopes' => json_encode(['read', 'write']),
        'status' => 'connected',
        'last_synced_at' => null,
        'connected_at' => now()->toDateTimeString(),
        'disconnected_at' => null,
        'metadata' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'deleted_at' => null,
        'purge_at' => null,
    ], $overrides));

    return $id;
}

it('lists social accounts for organization', function () {
    insertSocialAccount($this->orgId, $this->user['id'], ['username' => 'acc_one']);
    insertSocialAccount($this->orgId, $this->user['id'], ['username' => 'acc_two']);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/social-accounts');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns empty list for org with no accounts', function () {
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/social-accounts');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('disconnects social account', function () {
    $accountId = insertSocialAccount($this->orgId, $this->user['id']);

    $response = $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/social-accounts/{$accountId}");

    $response->assertNoContent();
});

it('returns 404 for non-existent account disconnect', function () {
    $fakeId = (string) Str::uuid();

    $response = $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/social-accounts/{$fakeId}");

    $response->assertStatus(404);
});

it('checks account health', function () {
    $accountId = insertSocialAccount($this->orgId, $this->user['id']);

    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/social-accounts/{$accountId}/health");

    $response->assertOk()
        ->assertJsonPath('data.status', 'connected')
        ->assertJsonPath('data.can_publish', true);
});

it('refreshes token', function () {
    $accountId = insertSocialAccount($this->orgId, $this->user['id']);

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/social-accounts/{$accountId}/refresh");

    $response->assertOk()
        ->assertJsonPath('data.provider', 'instagram');
});

it('prevents access to other org accounts', function () {
    // Create another user + org
    $otherUser = $this->createUserInDb(['email' => 'other@example.com']);
    $otherOrgData = $this->createOrgWithOwner($otherUser['id'], ['name' => 'Other Org', 'slug' => 'other-org']);
    $otherOrgId = $otherOrgData['org']['id'];

    $accountId = insertSocialAccount($otherOrgId, $otherUser['id']);

    // Try to access with our headers (our org context)
    // Should return 403 (authorization) or 404 (not found in tenant scope)
    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/social-accounts/{$accountId}/health");

    $response->assertStatus(403);
});

it('requires authentication for all endpoints', function () {
    $response = $this->getJson('/api/v1/social-accounts');
    $response->assertStatus(401);

    $response = $this->deleteJson('/api/v1/social-accounts/'.Str::uuid());
    $response->assertStatus(401);

    $response = $this->getJson('/api/v1/social-accounts/'.Str::uuid().'/health');
    $response->assertStatus(401);
});
