<?php

declare(strict_types=1);

use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->connector = createPipedriveConnector();
});

it('builds authorization url with correct params', function () {
    $url = $this->connector->getAuthorizationUrl('pipedrive-state-456');

    expect($url)->toContain('https://oauth.pipedrive.com/oauth/authorize')
        ->and($url)->toContain('client_id=pipedrive-client-id')
        ->and($url)->toContain('redirect_uri=' . urlencode('https://app.example.com/crm/pipedrive/callback'))
        ->and($url)->toContain('state=pipedrive-state-456');
});

it('authenticates with authorization code', function () {
    Http::fake([
        'https://oauth.pipedrive.com/oauth/token' => Http::response([
            'access_token' => 'pipedrive-access-token',
            'refresh_token' => 'pipedrive-refresh-token',
            'expires_in' => 3600,
            'api_domain' => 'api.pipedrive.com',
        ]),
        'https://api.pipedrive.com/api/v1/users/me*' => Http::response([
            'success' => true,
            'data' => [
                'id' => 12345,
                'name' => 'John Admin',
                'company_id' => 98765,
                'company_name' => 'Test Company',
            ],
        ]),
    ]);

    $result = $this->connector->authenticate('pipedrive-code', 'pipedrive-state-456');

    expect($result)->toHaveKey('access_token')
        ->and($result)->toHaveKey('refresh_token')
        ->and($result)->toHaveKey('account_id')
        ->and($result)->toHaveKey('account_name')
        ->and($result['access_token'])->toBe('pipedrive-access-token')
        ->and($result['account_name'])->toBe('Test Company');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'oauth.pipedrive.com/oauth/token'));
});

it('refreshes access token', function () {
    Http::fake([
        'https://oauth.pipedrive.com/oauth/token' => Http::response([
            'access_token' => 'new-pipedrive-token',
            'refresh_token' => 'new-pipedrive-refresh',
            'expires_in' => 3600,
        ]),
    ]);

    $result = $this->connector->refreshToken('old-pipedrive-refresh-token');

    expect($result)->toHaveKey('access_token')
        ->and($result['access_token'])->toBe('new-pipedrive-token');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'oauth.pipedrive.com/oauth/token')
            && $request['grant_type'] === 'refresh_token';
    });
});

it('revokes access token', function () {
    Http::fake([
        'https://oauth.pipedrive.com/oauth/revoke' => Http::response([], 200),
    ]);

    $this->connector->revokeToken('pipedrive-token-to-revoke');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'oauth.pipedrive.com/oauth/revoke'));
});

it('creates person in Pipedrive', function () {
    Http::fake([
        'https://api.pipedrive.com/api/v1/persons' => Http::response([
            'success' => true,
            'data' => [
                'id' => 789,
                'name' => 'Alice Johnson',
                'email' => [
                    ['value' => 'alice@example.com', 'primary' => true],
                ],
                'phone' => [
                    ['value' => '+1234567890', 'primary' => true],
                ],
            ],
        ], 201),
    ]);

    $result = $this->connector->createContact('pipedrive-token', [
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'phone' => '+1234567890',
    ]);

    expect($result)->toHaveKey('id')
        ->and($result)->toHaveKey('data')
        ->and($result['id'])->toBe('789')
        ->and($result['data']['name'])->toBe('Alice Johnson');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'pipedrive.com/api/v1/persons')
            && $request->method() === 'POST';
    });
});

it('updates existing person', function () {
    Http::fake([
        'https://api.pipedrive.com/api/v1/persons/*' => Http::response([
            'success' => true,
            'data' => [
                'id' => 999,
                'name' => 'Alice Johnson Updated',
                'email' => [
                    ['value' => 'alice.new@example.com', 'primary' => true],
                ],
            ],
        ]),
    ]);

    $result = $this->connector->updateContact('pipedrive-token', '999', [
        'name' => 'Alice Johnson Updated',
        'email' => 'alice.new@example.com',
    ]);

    expect($result['id'])->toBe('999')
        ->and($result['data']['name'])->toBe('Alice Johnson Updated');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'pipedrive.com/api/v1/persons/999')
            && $request->method() === 'PUT';
    });
});

it('creates deal in Pipedrive', function () {
    Http::fake([
        'https://api.pipedrive.com/api/v1/deals' => Http::response([
            'success' => true,
            'data' => [
                'id' => 5555,
                'title' => 'New Business Deal',
                'value' => 75000,
                'currency' => 'USD',
                'status' => 'open',
                'stage_id' => 1,
            ],
        ], 201),
    ]);

    $result = $this->connector->createDeal('pipedrive-token', [
        'title' => 'New Business Deal',
        'value' => 75000,
        'currency' => 'USD',
    ]);

    expect($result['id'])->toBe('5555')
        ->and($result['data']['title'])->toBe('New Business Deal')
        ->and($result['data']['value'])->toBe(75000);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'pipedrive.com/api/v1/deals'));
});

it('logs activity/note for person', function () {
    Http::fake([
        'https://api.pipedrive.com/api/v1/notes' => Http::response([
            'success' => true,
            'data' => [
                'id' => 3333,
                'content' => 'Customer showed interest on social media',
                'person_id' => 789,
                'add_time' => '2026-02-28 14:00:00',
            ],
        ], 201),
    ]);

    $result = $this->connector->logActivity('pipedrive-token', '789', [
        'content' => 'Customer showed interest on social media',
        'person_id' => 789,
    ]);

    expect($result['id'])->toBe('3333')
        ->and($result['data']['content'])->toBe('Customer showed interest on social media');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'pipedrive.com/api/v1/notes'));
});

it('searches persons by name', function () {
    Http::fake([
        'https://api.pipedrive.com/api/v1/persons/search*' => Http::response([
            'success' => true,
            'data' => [
                'items' => [
                    [
                        'item' => [
                            'id' => 101,
                            'name' => 'Bob Smith',
                            'emails' => ['bob@example.com'],
                        ],
                    ],
                    [
                        'item' => [
                            'id' => 102,
                            'name' => 'Bobby Jones',
                            'emails' => ['bobby@example.com'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $results = $this->connector->searchContacts('pipedrive-token', 'Bob');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(2)
        ->and($results[0])->toHaveKey('name')
        ->and($results[1])->toHaveKey('emails');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'pipedrive.com/api/v1/persons/search')
            && $request->method() === 'GET';
    });
});

it('checks connection status', function () {
    Http::fake([
        'https://api.pipedrive.com/api/v1/users/me*' => Http::response([
            'success' => true,
            'data' => [
                'id' => 12345,
                'name' => 'Test User',
            ],
        ]),
    ]);

    $status = $this->connector->getConnectionStatus('valid-pipedrive-token');

    expect($status)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'pipedrive.com/api/v1/users/me'));
});

it('returns false when connection is invalid', function () {
    Http::fake([
        'https://api.pipedrive.com/api/v1/users/me*' => Http::response([
            'success' => false,
            'error' => 'Unauthorized',
        ], 401),
    ]);

    $status = $this->connector->getConnectionStatus('invalid-pipedrive-token');

    expect($status)->toBeFalse();
});

it('implements CrmConnectorInterface', function () {
    expect($this->connector)->toBeInstanceOf(CrmConnectorInterface::class);
});

function createPipedriveConnector(): CrmConnectorInterface
{
    return new class([
        'client_id' => 'pipedrive-client-id',
        'client_secret' => 'pipedrive-client-secret',
        'redirect_uri' => 'https://app.example.com/crm/pipedrive/callback',
    ]) implements CrmConnectorInterface {
        private string $apiDomain = 'api.pipedrive.com';

        public function __construct(private array $config) {}

        public function getAuthorizationUrl(string $state): string
        {
            $params = [
                'client_id' => $this->config['client_id'],
                'redirect_uri' => $this->config['redirect_uri'],
                'state' => $state,
            ];

            return 'https://oauth.pipedrive.com/oauth/authorize?' . http_build_query($params);
        }

        public function authenticate(string $code, string $state): array
        {
            $response = Http::withBasicAuth($this->config['client_id'], $this->config['client_secret'])
                ->post('https://oauth.pipedrive.com/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->config['redirect_uri'],
                ]);

            $data = $response->json();

            if (isset($data['api_domain'])) {
                $this->apiDomain = $data['api_domain'];
            }

            $userInfo = Http::get("https://{$this->apiDomain}/api/v1/users/me", [
                'api_token' => $data['access_token'],
            ])->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in'])->toIso8601String(),
                'account_id' => (string) $userInfo['data']['company_id'],
                'account_name' => $userInfo['data']['company_name'],
            ];
        }

        public function refreshToken(string $refreshToken): array
        {
            $response = Http::withBasicAuth($this->config['client_id'], $this->config['client_secret'])
                ->post('https://oauth.pipedrive.com/oauth/token', [
                    'grant_type' => 'refresh_token',
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
            Http::withBasicAuth($this->config['client_id'], $this->config['client_secret'])
                ->post('https://oauth.pipedrive.com/oauth/revoke', [
                    'token' => $accessToken,
                ]);
        }

        public function createContact(string $accessToken, array $contactData): array
        {
            $response = Http::post("https://{$this->apiDomain}/api/v1/persons", [
                ...$contactData,
                'api_token' => $accessToken,
            ]);

            $data = $response->json();

            return [
                'id' => (string) $data['data']['id'],
                'data' => $data['data'],
            ];
        }

        public function updateContact(string $accessToken, string $contactId, array $data): array
        {
            $response = Http::put("https://{$this->apiDomain}/api/v1/persons/{$contactId}", [
                ...$data,
                'api_token' => $accessToken,
            ]);

            $result = $response->json();

            return [
                'id' => (string) $result['data']['id'],
                'data' => $result['data'],
            ];
        }

        public function createDeal(string $accessToken, array $dealData): array
        {
            $response = Http::post("https://{$this->apiDomain}/api/v1/deals", [
                ...$dealData,
                'api_token' => $accessToken,
            ]);

            $data = $response->json();

            return [
                'id' => (string) $data['data']['id'],
                'data' => $data['data'],
            ];
        }

        public function logActivity(string $accessToken, string $entityId, array $activityData): array
        {
            $response = Http::post("https://{$this->apiDomain}/api/v1/notes", [
                ...$activityData,
                'api_token' => $accessToken,
            ]);

            $data = $response->json();

            return [
                'id' => (string) $data['data']['id'],
                'data' => $data['data'],
            ];
        }

        public function searchContacts(string $accessToken, string $query): array
        {
            $response = Http::get("https://{$this->apiDomain}/api/v1/persons/search", [
                'term' => $query,
                'api_token' => $accessToken,
            ]);

            $data = $response->json();

            return array_map(
                fn ($item) => $item['item'],
                $data['data']['items'] ?? []
            );
        }

        public function getConnectionStatus(string $accessToken): bool
        {
            $response = Http::get("https://{$this->apiDomain}/api/v1/users/me", [
                'api_token' => $accessToken,
            ]);

            return $response->successful() && ($response->json()['success'] ?? false);
        }
    };
}
