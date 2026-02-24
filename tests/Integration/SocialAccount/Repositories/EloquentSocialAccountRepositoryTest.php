<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\ConnectionStatus;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(SocialAccountRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'repo-test-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'repo-test-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

function createSocialAccount(string $orgId, string $userId, array $overrides = []): SocialAccount
{
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('encrypted-access-token'),
        refreshToken: EncryptedToken::fromEncrypted('encrypted-refresh-token'),
        expiresAt: new DateTimeImmutable('+1 hour'),
        scopes: ['read', 'write'],
    );

    return SocialAccount::create(
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::fromString($userId),
        provider: $overrides['provider'] ?? SocialProvider::Instagram,
        providerUserId: $overrides['provider_user_id'] ?? 'ig-user-123',
        username: $overrides['username'] ?? 'testuser',
        credentials: $overrides['credentials'] ?? $credentials,
        displayName: $overrides['display_name'] ?? 'Test Display',
        profilePictureUrl: $overrides['profile_picture_url'] ?? 'https://example.com/pic.jpg',
        metadata: $overrides['metadata'] ?? ['followers' => 1000],
    );
}

it('creates and retrieves by id', function () {
    $account = createSocialAccount($this->orgId, $this->userId);
    $this->repository->create($account);

    $found = $this->repository->findById($account->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $account->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->provider)->toBe(SocialProvider::Instagram)
        ->and($found->providerUserId)->toBe('ig-user-123')
        ->and($found->username)->toBe('testuser')
        ->and($found->displayName)->toBe('Test Display')
        ->and($found->status)->toBe(ConnectionStatus::Connected)
        ->and($found->credentials->scopes)->toBe(['read', 'write']);
});

it('returns null for non-existent id', function () {
    $found = $this->repository->findById(Uuid::generate());

    expect($found)->toBeNull();
});

it('updates account fields', function () {
    $account = createSocialAccount($this->orgId, $this->userId);
    $this->repository->create($account);

    $updated = $account->updateProfile(username: 'newuser', displayName: 'New Display');
    $this->repository->update($updated);

    $found = $this->repository->findById($account->id);

    expect($found->username)->toBe('newuser')
        ->and($found->displayName)->toBe('New Display');
});

it('finds by organization id', function () {
    $account1 = createSocialAccount($this->orgId, $this->userId, ['provider_user_id' => 'user-1', 'username' => 'user1']);
    $account2 = createSocialAccount($this->orgId, $this->userId, ['provider_user_id' => 'user-2', 'username' => 'user2']);
    $this->repository->create($account1);
    $this->repository->create($account2);

    $results = $this->repository->findByOrganizationId(Uuid::fromString($this->orgId));

    expect($results)->toHaveCount(2);
});

it('finds by organization and provider', function () {
    $ig = createSocialAccount($this->orgId, $this->userId, [
        'provider' => SocialProvider::Instagram,
        'provider_user_id' => 'ig-1',
        'username' => 'ig_user',
    ]);
    $tt = createSocialAccount($this->orgId, $this->userId, [
        'provider' => SocialProvider::TikTok,
        'provider_user_id' => 'tt-1',
        'username' => 'tt_user',
    ]);
    $this->repository->create($ig);
    $this->repository->create($tt);

    $results = $this->repository->findByOrganizationAndProvider(
        Uuid::fromString($this->orgId),
        SocialProvider::Instagram,
    );

    expect($results)->toHaveCount(1)
        ->and($results[0]->provider)->toBe(SocialProvider::Instagram);
});

it('finds by provider and provider user id', function () {
    $account = createSocialAccount($this->orgId, $this->userId, [
        'provider' => SocialProvider::TikTok,
        'provider_user_id' => 'unique-tt-id',
        'username' => 'ttuser',
    ]);
    $this->repository->create($account);

    $found = $this->repository->findByProviderAndProviderUserId(SocialProvider::TikTok, 'unique-tt-id');

    expect($found)->not->toBeNull()
        ->and($found->providerUserId)->toBe('unique-tt-id');
});

it('soft deletes account', function () {
    $account = createSocialAccount($this->orgId, $this->userId);
    $this->repository->create($account);

    $this->repository->delete($account->id);

    $found = $this->repository->findById($account->id);

    expect($found)->toBeNull();
});

it('finds expiring tokens within threshold', function () {
    $soonCredentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('token-soon'),
        refreshToken: null,
        expiresAt: new DateTimeImmutable('+30 minutes'),
        scopes: [],
    );
    $farCredentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('token-far'),
        refreshToken: null,
        expiresAt: new DateTimeImmutable('+12 hours'),
        scopes: [],
    );

    $soon = createSocialAccount($this->orgId, $this->userId, [
        'credentials' => $soonCredentials,
        'provider_user_id' => 'soon-user',
        'username' => 'soon',
    ]);
    $far = createSocialAccount($this->orgId, $this->userId, [
        'credentials' => $farCredentials,
        'provider_user_id' => 'far-user',
        'username' => 'far',
    ]);

    $this->repository->create($soon);
    $this->repository->create($far);

    $expiring = $this->repository->findExpiringTokens(60); // 60 min threshold

    expect($expiring)->toHaveCount(1)
        ->and($expiring[0]->username)->toBe('soon');
});

it('excludes deleted accounts from expiring tokens', function () {
    $soonCredentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('token-deleted'),
        refreshToken: null,
        expiresAt: new DateTimeImmutable('+30 minutes'),
        scopes: [],
    );

    $account = createSocialAccount($this->orgId, $this->userId, [
        'credentials' => $soonCredentials,
        'provider_user_id' => 'deleted-user',
        'username' => 'deleted',
    ]);

    $this->repository->create($account);

    // Soft delete via domain then update
    $deleted = $account->softDelete();
    $this->repository->update($deleted);

    $expiring = $this->repository->findExpiringTokens(60);

    expect($expiring)->toBeEmpty();
});
