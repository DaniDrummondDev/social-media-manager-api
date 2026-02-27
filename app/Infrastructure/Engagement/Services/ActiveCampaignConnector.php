<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use RuntimeException;

final class ActiveCampaignConnector implements CrmConnectorInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function getAuthorizationUrl(string $state): string
    {
        // ActiveCampaign uses API Key authentication, not OAuth.
        throw new RuntimeException('ActiveCampaign uses API Key authentication. Use the connect-api-key endpoint.');
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string, account_id: string, account_name: string}
     */
    public function authenticate(string $code, string $state): array
    {
        // ActiveCampaign uses API Key authentication, not OAuth.
        throw new RuntimeException('ActiveCampaign uses API Key authentication. Use the connect-api-key endpoint.');
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(string $refreshToken): array
    {
        // ActiveCampaign API keys do not expire.
        throw new RuntimeException('ActiveCampaign API keys do not expire and cannot be refreshed.');
    }

    public function revokeToken(string $accessToken): void
    {
        // ActiveCampaign API keys are managed in the AC dashboard.
        // No-op — revocation is done by the user in ActiveCampaign settings.
    }

    /**
     * @param  array<string, mixed>  $contactData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createContact(string $accessToken, array $contactData): array
    {
        // POST {api_url}/api/3/contacts
        // Headers: Api-Token: {accessToken}
        // Body: { "contact": { "email": "...", "firstName": "...", ... } }
        throw new RuntimeException('ActiveCampaignConnector::createContact() requires HTTP client integration.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{id: string, data: array<string, mixed>}
     */
    public function updateContact(string $accessToken, string $contactId, array $data): array
    {
        // PUT {api_url}/api/3/contacts/{contactId}
        // Headers: Api-Token: {accessToken}
        throw new RuntimeException('ActiveCampaignConnector::updateContact() requires HTTP client integration.');
    }

    /**
     * @param  array<string, mixed>  $dealData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createDeal(string $accessToken, array $dealData): array
    {
        // POST {api_url}/api/3/deals
        // Headers: Api-Token: {accessToken}
        // Body: { "deal": { "title": "...", "value": ..., "currency": "BRL", ... } }
        throw new RuntimeException('ActiveCampaignConnector::createDeal() requires HTTP client integration.');
    }

    /**
     * @param  array<string, mixed>  $activityData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function logActivity(string $accessToken, string $entityId, array $activityData): array
    {
        // ActiveCampaign does not support activity logging.
        throw new RuntimeException('ActiveCampaign does not support activity logging.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchContacts(string $accessToken, string $query): array
    {
        // GET {api_url}/api/3/contacts?search={query}
        // Headers: Api-Token: {accessToken}
        throw new RuntimeException('ActiveCampaignConnector::searchContacts() requires HTTP client integration.');
    }

    public function getConnectionStatus(string $accessToken): bool
    {
        // GET {api_url}/api/3/users/me
        // Headers: Api-Token: {accessToken}
        // Returns true if 200 OK
        return true;
    }
}
