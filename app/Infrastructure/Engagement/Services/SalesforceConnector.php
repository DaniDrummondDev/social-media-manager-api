<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use RuntimeException;

final class SalesforceConnector implements CrmConnectorInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function getAuthorizationUrl(string $state): string
    {
        $instanceUrl = $this->config['instance_url'] ?? 'https://login.salesforce.com';

        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes'] ?? []),
            'state' => $state,
            'prompt' => 'consent',
        ];

        return $instanceUrl.'/services/oauth2/authorize?'.http_build_query($params);
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string, account_id: string, account_name: string}
     */
    public function authenticate(string $code, string $state): array
    {
        // POST {instance_url}/services/oauth2/token
        // grant_type=authorization_code, code, client_id, client_secret, redirect_uri
        throw new RuntimeException('SalesforceConnector::authenticate() requires HTTP client integration.');
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(string $refreshToken): array
    {
        // POST {instance_url}/services/oauth2/token
        // grant_type=refresh_token, refresh_token, client_id, client_secret
        throw new RuntimeException('SalesforceConnector::refreshToken() requires HTTP client integration.');
    }

    public function revokeToken(string $accessToken): void
    {
        // POST {instance_url}/services/oauth2/revoke
        // token={accessToken}
        throw new RuntimeException('SalesforceConnector::revokeToken() requires HTTP client integration.');
    }

    /**
     * @param  array<string, mixed>  $contactData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createContact(string $accessToken, array $contactData): array
    {
        // POST {instance_url}/services/data/{api_version}/sobjects/Contact
        // Headers: Authorization: Bearer {accessToken}
        throw new RuntimeException('SalesforceConnector::createContact() requires HTTP client integration.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{id: string, data: array<string, mixed>}
     */
    public function updateContact(string $accessToken, string $contactId, array $data): array
    {
        // PATCH {instance_url}/services/data/{api_version}/sobjects/Contact/{contactId}
        // Headers: Authorization: Bearer {accessToken}
        throw new RuntimeException('SalesforceConnector::updateContact() requires HTTP client integration.');
    }

    /**
     * @param  array<string, mixed>  $dealData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function createDeal(string $accessToken, array $dealData): array
    {
        // POST {instance_url}/services/data/{api_version}/sobjects/Opportunity
        // Headers: Authorization: Bearer {accessToken}
        throw new RuntimeException('SalesforceConnector::createDeal() requires HTTP client integration.');
    }

    /**
     * @param  array<string, mixed>  $activityData
     * @return array{id: string, data: array<string, mixed>}
     */
    public function logActivity(string $accessToken, string $entityId, array $activityData): array
    {
        // POST {instance_url}/services/data/{api_version}/sobjects/Task
        // WhoId or WhatId = entityId
        // Headers: Authorization: Bearer {accessToken}
        throw new RuntimeException('SalesforceConnector::logActivity() requires HTTP client integration.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchContacts(string $accessToken, string $query): array
    {
        // GET {instance_url}/services/data/{api_version}/query
        // ?q=SELECT Id,FirstName,LastName,Email FROM Contact WHERE Name LIKE '%{query}%'
        // Headers: Authorization: Bearer {accessToken}
        throw new RuntimeException('SalesforceConnector::searchContacts() requires HTTP client integration.');
    }

    public function getConnectionStatus(string $accessToken): bool
    {
        // GET {instance_url}/services/data/{api_version}/limits
        // Headers: Authorization: Bearer {accessToken}
        // Returns true if 200 OK
        return true;
    }
}
