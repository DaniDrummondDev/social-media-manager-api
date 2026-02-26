<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Domain\Engagement\Contracts\CrmConnectorInterface;

final class StubCrmConnector implements CrmConnectorInterface
{
    public function __construct(
        private readonly string $provider = 'stub',
    ) {}

    public function getAuthorizationUrl(string $state): string
    {
        return "https://crm-stub.example.com/oauth/authorize?state={$state}&provider={$this->provider}";
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string, account_id: string, account_name: string}
     */
    public function authenticate(string $code, string $state): array
    {
        return [
            'access_token' => 'stub_access_token_'.bin2hex(random_bytes(16)),
            'refresh_token' => 'stub_refresh_token_'.bin2hex(random_bytes(16)),
            'expires_at' => now()->addHour()->toIso8601String(),
            'account_id' => 'stub_account_'.bin2hex(random_bytes(8)),
            'account_name' => 'Stub CRM Account',
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(string $refreshToken): array
    {
        return [
            'access_token' => 'stub_refreshed_token_'.bin2hex(random_bytes(16)),
            'refresh_token' => 'stub_refresh_token_'.bin2hex(random_bytes(16)),
            'expires_at' => now()->addHour()->toIso8601String(),
        ];
    }

    public function revokeToken(string $accessToken): void
    {
        // Stub — no-op
    }

    /**
     * @param  array<string, mixed>  $contactData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createContact(string $accessToken, array $contactData): array
    {
        return [
            'id' => 'crm_contact_'.bin2hex(random_bytes(8)),
            'data' => $contactData,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{id: string, data: array<string, mixed>}
     */
    public function updateContact(string $accessToken, string $contactId, array $data): array
    {
        return [
            'id' => $contactId,
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $dealData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createDeal(string $accessToken, array $dealData): array
    {
        return [
            'id' => 'crm_deal_'.bin2hex(random_bytes(8)),
            'data' => $dealData,
        ];
    }

    /**
     * @param  array<string, mixed>  $activityData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function logActivity(string $accessToken, string $entityId, array $activityData): array
    {
        return [
            'id' => 'crm_activity_'.bin2hex(random_bytes(8)),
            'data' => $activityData,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchContacts(string $accessToken, string $query): array
    {
        return [];
    }

    public function getConnectionStatus(string $accessToken): bool
    {
        return true;
    }
}
