<?php

declare(strict_types=1);

use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->connector = createRDStationConnector();
});

it('builds authorization url with correct params', function () {
    $url = $this->connector->getAuthorizationUrl('rdstation-state-123');

    expect($url)->toContain('https://api.rd.services/auth/dialog')
        ->and($url)->toContain('client_id=rdstation-client-id')
        ->and($url)->toContain('redirect_uri=' . urlencode('https://app.example.com/crm/rdstation/callback'))
        ->and($url)->toContain('state=rdstation-state-123');
});

it('authenticates with authorization code', function () {
    Http::fake([
        'api.rd.services/auth/token' => Http::response([
            'access_token' => 'rdstation-access-token',
            'refresh_token' => 'rdstation-refresh-token',
            'expires_in' => 86400,
        ]),
        'api.rd.services/platform/accounts' => Http::response([
            'uuid' => 'account-uuid-123',
            'name' => 'RD Station Test Account',
        ]),
    ]);

    $result = $this->connector->authenticate('rd-auth-code', 'rdstation-state-123');

    expect($result)->toHaveKey('access_token')
        ->and($result)->toHaveKey('refresh_token')
        ->and($result)->toHaveKey('account_id')
        ->and($result)->toHaveKey('account_name')
        ->and($result['access_token'])->toBe('rdstation-access-token')
        ->and($result['account_name'])->toBe('RD Station Test Account');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.rd.services/auth/token'));
});

it('refreshes access token', function () {
    Http::fake([
        'api.rd.services/auth/token' => Http::response([
            'access_token' => 'new-rdstation-token',
            'refresh_token' => 'new-rdstation-refresh',
            'expires_in' => 86400,
        ]),
    ]);

    $result = $this->connector->refreshToken('old-rdstation-refresh-token');

    expect($result)->toHaveKey('access_token')
        ->and($result['access_token'])->toBe('new-rdstation-token');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.rd.services/auth/token')
            && $request['grant_type'] === 'refresh_token';
    });
});

it('revokes access token', function () {
    Http::fake([
        'api.rd.services/auth/revoke' => Http::response([], 204),
    ]);

    $this->connector->revokeToken('rdstation-token-to-revoke');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.rd.services/auth/revoke'));
});

it('creates contact in RD Station', function () {
    Http::fake([
        'api.rd.services/platform/contacts' => Http::response([
            'uuid' => 'contact-uuid-456',
            'email' => 'maria@example.com',
            'name' => 'Maria Silva',
            'job_title' => 'Marketing Manager',
            'created_at' => '2026-02-28T10:00:00-03:00',
        ], 201),
    ]);

    $result = $this->connector->createContact('rdstation-access-token', [
        'email' => 'maria@example.com',
        'name' => 'Maria Silva',
        'job_title' => 'Marketing Manager',
    ]);

    expect($result)->toHaveKey('id')
        ->and($result)->toHaveKey('data')
        ->and($result['id'])->toBe('contact-uuid-456')
        ->and($result['data']['email'])->toBe('maria@example.com');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.rd.services/platform/contacts')
            && $request->hasHeader('Authorization', 'Bearer rdstation-access-token')
            && $request->method() === 'POST';
    });
});

it('updates existing contact', function () {
    Http::fake([
        'api.rd.services/platform/contacts/email:*' => Http::response([
            'uuid' => 'contact-uuid-789',
            'email' => 'updated@example.com',
            'name' => 'Maria Silva',
            'updated_at' => '2026-02-28T11:00:00-03:00',
        ]),
    ]);

    $result = $this->connector->updateContact('rdstation-access-token', 'email:updated@example.com', [
        'name' => 'Maria Silva',
    ]);

    expect($result['id'])->toBe('contact-uuid-789')
        ->and($result['data']['email'])->toBe('updated@example.com');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.rd.services/platform/contacts/email:')
            && $request->method() === 'PATCH';
    });
});

it('creates deal/opportunity in RD Station', function () {
    Http::fake([
        'api.rd.services/platform/deals' => Http::response([
            'id' => 'deal-rd-101',
            'name' => 'Nova Oportunidade',
            'deal_stage_id' => 'stage-1',
            'amount' => 25000.00,
            'created_at' => '2026-02-28T12:00:00-03:00',
        ], 201),
    ]);

    $result = $this->connector->createDeal('rdstation-access-token', [
        'name' => 'Nova Oportunidade',
        'deal_stage_id' => 'stage-1',
        'amount' => 25000.00,
    ]);

    expect($result['id'])->toBe('deal-rd-101')
        ->and($result['data']['name'])->toBe('Nova Oportunidade')
        ->and($result['data']['amount'])->toEqual(25000);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.rd.services/platform/deals'));
});

it('logs activity for contact', function () {
    Http::fake([
        'api.rd.services/platform/events' => Http::response([
            'event_uuid' => 'event-rd-202',
            'event_type' => 'CONVERSION',
            'created_at' => '2026-02-28T13:00:00-03:00',
        ], 201),
    ]);

    $result = $this->connector->logActivity('rdstation-access-token', 'contact-uuid-123', [
        'event_type' => 'CONVERSION',
        'event_family' => 'CDP',
        'payload' => [
            'conversion_identifier' => 'instagram-engagement',
            'email' => 'contact@example.com',
        ],
    ]);

    expect($result['id'])->toBe('event-rd-202')
        ->and($result['data']['event_type'])->toBe('CONVERSION');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.rd.services/platform/events'));
});

it('searches contacts by email', function () {
    Http::fake([
        'api.rd.services/platform/contacts*' => Http::response([
            'contacts' => [
                [
                    'uuid' => 'contact-111',
                    'email' => 'pedro@example.com',
                    'name' => 'Pedro Santos',
                ],
                [
                    'uuid' => 'contact-222',
                    'email' => 'pedro.silva@example.com',
                    'name' => 'Pedro Silva',
                ],
            ],
        ]),
    ]);

    $results = $this->connector->searchContacts('rdstation-access-token', 'pedro');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(2)
        ->and($results[0])->toHaveKey('email')
        ->and($results[1])->toHaveKey('name');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.rd.services/platform/contacts')
            && $request->method() === 'GET';
    });
});

it('checks connection status', function () {
    Http::fake([
        'api.rd.services/platform/accounts' => Http::response([
            'uuid' => 'account-123',
            'name' => 'Test Account',
        ]),
    ]);

    $status = $this->connector->getConnectionStatus('valid-rdstation-token');

    expect($status)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.rd.services/platform/accounts'));
});

it('returns false when connection is invalid', function () {
    Http::fake([
        'api.rd.services/platform/accounts' => Http::response([
            'errors' => [
                ['error_type' => 'UNAUTHORIZED'],
            ],
        ], 401),
    ]);

    $status = $this->connector->getConnectionStatus('invalid-rdstation-token');

    expect($status)->toBeFalse();
});

it('implements CrmConnectorInterface', function () {
    expect($this->connector)->toBeInstanceOf(CrmConnectorInterface::class);
});

function createRDStationConnector(): CrmConnectorInterface
{
    return new class([
        'client_id' => 'rdstation-client-id',
        'client_secret' => 'rdstation-client-secret',
        'redirect_uri' => 'https://app.example.com/crm/rdstation/callback',
    ]) implements CrmConnectorInterface {
        public function __construct(private array $config) {}

        public function getAuthorizationUrl(string $state): string
        {
            $params = [
                'client_id' => $this->config['client_id'],
                'redirect_uri' => $this->config['redirect_uri'],
                'state' => $state,
            ];

            return 'https://api.rd.services/auth/dialog?' . http_build_query($params);
        }

        public function authenticate(string $code, string $state): array
        {
            $response = Http::post('https://api.rd.services/auth/token', [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'code' => $code,
            ]);

            $data = $response->json();

            $accountInfo = Http::withToken($data['access_token'])
                ->get('https://api.rd.services/platform/accounts')
                ->json();

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in'])->toIso8601String(),
                'account_id' => $accountInfo['uuid'],
                'account_name' => $accountInfo['name'],
            ];
        }

        public function refreshToken(string $refreshToken): array
        {
            $response = Http::post('https://api.rd.services/auth/token', [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
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
            Http::withToken($accessToken)->post('https://api.rd.services/auth/revoke');
        }

        public function createContact(string $accessToken, array $contactData): array
        {
            $response = Http::withToken($accessToken)
                ->post('https://api.rd.services/platform/contacts', $contactData);

            $data = $response->json();

            return [
                'id' => $data['uuid'],
                'data' => $data,
            ];
        }

        public function updateContact(string $accessToken, string $contactId, array $data): array
        {
            $response = Http::withToken($accessToken)
                ->patch("https://api.rd.services/platform/contacts/{$contactId}", $data);

            $result = $response->json();

            return [
                'id' => $result['uuid'],
                'data' => $result,
            ];
        }

        public function createDeal(string $accessToken, array $dealData): array
        {
            $response = Http::withToken($accessToken)
                ->post('https://api.rd.services/platform/deals', $dealData);

            $data = $response->json();

            return [
                'id' => (string) $data['id'],
                'data' => $data,
            ];
        }

        public function logActivity(string $accessToken, string $entityId, array $activityData): array
        {
            $response = Http::withToken($accessToken)
                ->post('https://api.rd.services/platform/events', $activityData);

            $data = $response->json();

            return [
                'id' => $data['event_uuid'],
                'data' => $data,
            ];
        }

        public function searchContacts(string $accessToken, string $query): array
        {
            $response = Http::withToken($accessToken)
                ->get('https://api.rd.services/platform/contacts', [
                    'email' => $query,
                ]);

            $data = $response->json();

            return $data['contacts'] ?? [];
        }

        public function getConnectionStatus(string $accessToken): bool
        {
            $response = Http::withToken($accessToken)
                ->get('https://api.rd.services/platform/accounts');

            return $response->successful();
        }
    };
}
