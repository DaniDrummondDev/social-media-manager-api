<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Domain\Identity\ValueObjects\HashedPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait InteractsWithAuth
{
    private FakeAuthTokenService $authService;

    protected function setUpAuth(): void
    {
        $this->app->singleton(AuthTokenServiceInterface::class, FakeAuthTokenService::class);
        $this->authService = $this->app->make(AuthTokenServiceInterface::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function createUserInDb(array $overrides = []): array
    {
        $id = $overrides['id'] ?? (string) Str::uuid();
        $plainPassword = $overrides['plain_password'] ?? 'SecureP@ss123!';

        $attrs = array_merge([
            'id' => $id,
            'name' => 'Test User',
            'email' => 'user-'.Str::random(6).'@example.com',
            'password' => (string) HashedPassword::fromPlainText($plainPassword),
            'timezone' => 'America/Sao_Paulo',
            'email_verified_at' => now()->toDateTimeString(),
            'two_factor_enabled' => false,
            'status' => 'active',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ], $overrides);

        unset($attrs['plain_password']);

        DB::table('users')->insert($attrs);

        return $attrs;
    }

    /**
     * @param  array<string, mixed>  $orgOverrides
     * @return array{org: array<string, mixed>, member: array<string, mixed>}
     */
    protected function createOrgWithOwner(string $userId, array $orgOverrides = []): array
    {
        $orgId = $orgOverrides['id'] ?? (string) Str::uuid();

        $org = array_merge([
            'id' => $orgId,
            'name' => 'Test Org',
            'slug' => 'test-org-'.Str::random(4),
            'timezone' => 'America/Sao_Paulo',
            'status' => 'active',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ], $orgOverrides);

        DB::table('organizations')->insert($org);

        $member = [
            'id' => (string) Str::uuid(),
            'organization_id' => $orgId,
            'user_id' => $userId,
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now()->toDateTimeString(),
        ];

        DB::table('organization_members')->insert($member);

        return ['org' => $org, 'member' => $member];
    }

    /**
     * @return array<string, string>
     */
    protected function authHeaders(
        string $userId,
        string $orgId = '',
        string $email = 'test@example.com',
        string $role = 'owner',
    ): array {
        $tokenData = $this->authService->generateAccessToken($userId, $orgId, $email, $role);

        return ['Authorization' => 'Bearer '.$tokenData['token']];
    }
}
