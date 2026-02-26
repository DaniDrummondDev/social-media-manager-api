<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Contracts;

interface CrmConnectorInterface
{
    /**
     * Build the provider-specific OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string;

    /**
     * Exchange authorization code for access credentials.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string, account_id: string, account_name: string}
     */
    public function authenticate(string $code, string $state): array;

    /**
     * Refresh an expired access token.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Revoke access and disconnect from the CRM.
     */
    public function revokeToken(string $accessToken): void;

    /**
     * Create a contact in the CRM.
     *
     * @param  array<string, mixed>  $contactData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createContact(string $accessToken, array $contactData): array;

    /**
     * Update an existing contact in the CRM.
     *
     * @param  array<string, mixed>  $data
     * @return array{id: string, data: array<string, mixed>}
     */
    public function updateContact(string $accessToken, string $contactId, array $data): array;

    /**
     * Create a deal/opportunity in the CRM.
     *
     * @param  array<string, mixed>  $dealData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createDeal(string $accessToken, array $dealData): array;

    /**
     * Log an activity/note against an entity in the CRM.
     *
     * @param  array<string, mixed>  $activityData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function logActivity(string $accessToken, string $entityId, array $activityData): array;

    /**
     * Search contacts in the CRM.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchContacts(string $accessToken, string $query): array;

    /**
     * Check if the connection/token is still valid.
     */
    public function getConnectionStatus(string $accessToken): bool;
}
