<?php

declare(strict_types=1);

use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->connector = createHubSpotConnector();
});

it('builds authorization url with correct params', function () {
    $url = $this->connector->getAuthorizationUrl('state-token-123');

    expect($url)->toContain('https://app.hubspot.com/oauth/authorize')
        ->and($url)->toContain('client_id=test-hubspot-client-id')
        ->and($url)->toContain('redirect_uri=' . urlencode('https://app.example.com/crm/hubspot/callback'))
        ->and($url)->toContain('state=state-token-123')
        ->and($url)->toContain('scope=' . urlencode('crm.objects.contacts.read crm.objects.contacts.write'));
});

it('authenticates with authorization code', function () {
    Http::fake([
        'api.hubapi.com/oauth/v1/token' => Http::response([
            'access_token' => 'hubspot-access-token',
            'refresh_token' => 'hubspot-refresh-token',
            'expires_in' => 3600,
        ]),
        'api.hubapi.com/oauth/v1/access-tokens/*' => Http::response([
            'hub_id' => '12345678',
            'hub_domain' => 'test-company',
        ]),
    ]);

    $result = $this->connector->authenticate('auth-code-123', 'state-token-123');

    expect($result)->toHaveKey('access_token')
        ->and($result)->toHaveKey('refresh_token')
        ->and($result)->toHaveKey('account_id')
        ->and($result)->toHaveKey('account_name')
        ->and($result['access_token'])->toBe('hubspot-access-token')
        ->and($result['refresh_token'])->toBe('hubspot-refresh-token');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.hubapi.com/oauth/v1/token'));
});

it('refreshes access token', function () {
    Http::fake([
        'api.hubapi.com/oauth/v1/token' => Http::response([
            'access_token' => 'new-hubspot-access-token',
            'refresh_token' => 'new-hubspot-refresh-token',
            'expires_in' => 3600,
        ]),
    ]);

    $result = $this->connector->refreshToken('old-refresh-token');

    expect($result)->toHaveKey('access_token')
        ->and($result)->toHaveKey('refresh_token')
        ->and($result['access_token'])->toBe('new-hubspot-access-token');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.hubapi.com/oauth/v1/token')
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'old-refresh-token';
    });
});

it('revokes access token', function () {
    Http::fake([
        'api.hubapi.com/oauth/v1/refresh-tokens/*' => Http::response([], 204),
    ]);

    $this->connector->revokeToken('access-token-to-revoke');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.hubapi.com/oauth/v1/refresh-tokens'));
});

it('creates contact in HubSpot', function () {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/contacts' => Http::response([
            'id' => 'hubspot-contact-123',
            'properties' => [
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
            ],
            'createdAt' => '2026-02-28T10:00:00Z',
            'updatedAt' => '2026-02-28T10:00:00Z',
        ], 201),
    ]);

    $result = $this->connector->createContact('access-token', [
        'email' => 'john@example.com',
        'firstname' => 'John',
        'lastname' => 'Doe',
    ]);

    expect($result)->toHaveKey('id')
        ->and($result)->toHaveKey('data')
        ->and($result['id'])->toBe('hubspot-contact-123')
        ->and($result['data']['email'])->toBe('john@example.com');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.hubapi.com/crm/v3/objects/contacts')
            && $request->hasHeader('Authorization', 'Bearer access-token')
            && $request->method() === 'POST';
    });
});

it('updates existing contact', function () {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/contacts/*' => Http::response([
            'id' => 'contact-456',
            'properties' => [
                'email' => 'updated@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
            ],
            'updatedAt' => '2026-02-28T11:00:00Z',
        ]),
    ]);

    $result = $this->connector->updateContact('access-token', 'contact-456', [
        'email' => 'updated@example.com',
    ]);

    expect($result['id'])->toBe('contact-456')
        ->and($result['data']['email'])->toBe('updated@example.com');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.hubapi.com/crm/v3/objects/contacts/contact-456')
            && $request->method() === 'PATCH';
    });
});

it('creates deal in HubSpot', function () {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals' => Http::response([
            'id' => 'deal-789',
            'properties' => [
                'dealname' => 'New Opportunity',
                'amount' => '50000',
                'pipeline' => 'default',
                'dealstage' => 'qualifiedtobuy',
            ],
            'createdAt' => '2026-02-28T12:00:00Z',
        ], 201),
    ]);

    $result = $this->connector->createDeal('access-token', [
        'dealname' => 'New Opportunity',
        'amount' => '50000',
        'pipeline' => 'default',
        'dealstage' => 'qualifiedtobuy',
    ]);

    expect($result['id'])->toBe('deal-789')
        ->and($result['data']['dealname'])->toBe('New Opportunity');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.hubapi.com/crm/v3/objects/deals'));
});

it('logs activity for contact', function () {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/notes' => Http::response([
            'id' => 'note-101',
            'properties' => [
                'hs_note_body' => 'Customer responded positively on Instagram',
                'hs_timestamp' => '2026-02-28T13:00:00Z',
            ],
            'createdAt' => '2026-02-28T13:00:00Z',
        ], 201),
    ]);

    $result = $this->connector->logActivity('access-token', 'contact-123', [
        'hs_note_body' => 'Customer responded positively on Instagram',
        'hs_timestamp' => '2026-02-28T13:00:00Z',
    ]);

    expect($result['id'])->toBe('note-101')
        ->and($result['data'])->toHaveKey('hs_note_body');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.hubapi.com/crm/v3/objects/notes'));
});

it('searches contacts by query', function () {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/contacts/search' => Http::response([
            'results' => [
                [
                    'id' => 'contact-111',
                    'properties' => [
                        'email' => 'john@example.com',
                        'firstname' => 'John',
                        'lastname' => 'Doe',
                    ],
                ],
                [
                    'id' => 'contact-222',
                    'properties' => [
                        'email' => 'jane@example.com',
                        'firstname' => 'Jane',
                        'lastname' => 'Doe',
                    ],
                ],
            ],
            'total' => 2,
        ]),
    ]);

    $results = $this->connector->searchContacts('access-token', 'Doe');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(2)
        ->and($results[0])->toHaveKey('email')
        ->and($results[1])->toHaveKey('firstname');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.hubapi.com/crm/v3/objects/contacts/search')
            && $request->method() === 'POST';
    });
});

it('checks connection status', function () {
    Http::fake([
        'api.hubapi.com/oauth/v1/access-tokens/*' => Http::response([
            'token' => 'access-token',
            'hub_id' => '12345678',
        ]),
    ]);

    $status = $this->connector->getConnectionStatus('valid-access-token');

    expect($status)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.hubapi.com/oauth/v1/access-tokens'));
});

it('returns false when connection is invalid', function () {
    Http::fake([
        'api.hubapi.com/oauth/v1/access-tokens/*' => Http::response([
            'error' => 'invalid_token',
        ], 401),
    ]);

    $status = $this->connector->getConnectionStatus('invalid-token');

    expect($status)->toBeFalse();
});

it('implements CrmConnectorInterface', function () {
    expect($this->connector)->toBeInstanceOf(CrmConnectorInterface::class);
});

function createHubSpotConnector(): CrmConnectorInterface
{
    return new class([
        'client_id' => 'test-hubspot-client-id',
        'client_secret' => 'test-hubspot-client-secret',
        'redirect_uri' => 'https://app.example.com/crm/hubspot/callback',
        'scopes' => ['crm.objects.contacts.read', 'crm.objects.contacts.write'],
    ]) implements CrmConnectorInterface {
        public function __construct(private array $config) {}

        public function getAuthorizationUrl(string $state): string
        {
            $params = [
                'client_id' => $this->config['client_id'],
                'redirect_uri' => $this->config['redirect_uri'],
                'scope' => implode(' ', $this->config['scopes']),
                'state' => $state,
            ];

            return 'https://app.hubspot.com/oauth/authorize?' . http_build_query($params);
        }

        public function authenticate(string $code, string $state): array
        {
            $response = Http::post('https://api.hubapi.com/oauth/v1/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'redirect_uri' => $this->config['redirect_uri'],
                'code' => $code,
            ]);

            $data = $response->json();
            $tokenInfo = Http::get("https://api.hubapi.com/oauth/v1/access-tokens/{$data['access_token']}")->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in'])->toIso8601String(),
                'account_id' => (string) $tokenInfo['hub_id'],
                'account_name' => $tokenInfo['hub_domain'],
            ];
        }

        public function refreshToken(string $refreshToken): array
        {
            $response = Http::post('https://api.hubapi.com/oauth/v1/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'refresh_token' => $refreshToken,
            ]);

            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in'])->toIso8601String(),
            ];
        }

        public function revokeToken(string $accessToken): void
        {
            Http::delete("https://api.hubapi.com/oauth/v1/refresh-tokens/{$accessToken}");
        }

        public function createContact(string $accessToken, array $contactData): array
        {
            $response = Http::withToken($accessToken)
                ->post('https://api.hubapi.com/crm/v3/objects/contacts', [
                    'properties' => $contactData,
                ]);

            $data = $response->json();

            return [
                'id' => $data['id'],
                'data' => $data['properties'],
            ];
        }

        public function updateContact(string $accessToken, string $contactId, array $data): array
        {
            $response = Http::withToken($accessToken)
                ->patch("https://api.hubapi.com/crm/v3/objects/contacts/{$contactId}", [
                    'properties' => $data,
                ]);

            $result = $response->json();

            return [
                'id' => $result['id'],
                'data' => $result['properties'],
            ];
        }

        public function createDeal(string $accessToken, array $dealData): array
        {
            $response = Http::withToken($accessToken)
                ->post('https://api.hubapi.com/crm/v3/objects/deals', [
                    'properties' => $dealData,
                ]);

            $data = $response->json();

            return [
                'id' => $data['id'],
                'data' => $data['properties'],
            ];
        }

        public function logActivity(string $accessToken, string $entityId, array $activityData): array
        {
            $response = Http::withToken($accessToken)
                ->post('https://api.hubapi.com/crm/v3/objects/notes', [
                    'properties' => $activityData,
                ]);

            $data = $response->json();

            return [
                'id' => $data['id'],
                'data' => $data['properties'],
            ];
        }

        public function searchContacts(string $accessToken, string $query): array
        {
            $response = Http::withToken($accessToken)
                ->post('https://api.hubapi.com/crm/v3/objects/contacts/search', [
                    'filterGroups' => [
                        [
                            'filters' => [
                                [
                                    'propertyName' => 'lastname',
                                    'operator' => 'CONTAINS_TOKEN',
                                    'value' => $query,
                                ],
                            ],
                        ],
                    ],
                ]);

            $data = $response->json();

            return array_map(fn ($contact) => $contact['properties'], $data['results']);
        }

        public function getConnectionStatus(string $accessToken): bool
        {
            $response = Http::get("https://api.hubapi.com/oauth/v1/access-tokens/{$accessToken}");

            return $response->successful();
        }
    };
}
