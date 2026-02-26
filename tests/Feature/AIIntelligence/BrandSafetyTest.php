<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);
});

function insertSafetyCheck(string $orgId, string $contentId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('brand_safety_checks')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'content_id' => $contentId,
        'provider' => null,
        'overall_status' => 'passed',
        'overall_score' => 100,
        'checks' => json_encode([
            ['category' => 'lgpd_compliance', 'status' => 'passed', 'message' => null, 'severity' => null],
        ]),
        'model_used' => 'gpt-4',
        'tokens_input' => 200,
        'tokens_output' => 80,
        'checked_at' => $now,
        'created_at' => $now,
    ], $overrides));

    return $id;
}

it('POST /contents/{id}/safety-check — 202', function () {
    Bus::fake();

    $contentId = Str::uuid()->toString();

    $response = $this->postJson("/api/v1/contents/{$contentId}/safety-check", [], $this->headers);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'data' => [
                'check_id',
                'content_id',
                'status',
                'message',
            ],
        ]);

    expect($response->json('data.status'))->toBe('pending')
        ->and($response->json('data.content_id'))->toBe($contentId);
});

it('GET /contents/{id}/safety-checks — 200 with checks', function () {
    $contentId = Str::uuid()->toString();
    insertSafetyCheck($this->orgId, $contentId);

    $response = $this->getJson("/api/v1/contents/{$contentId}/safety-checks", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'content_id',
                        'overall_status',
                        'overall_score',
                        'checks',
                        'checked_at',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('GET /contents/{id}/safety-checks — 200 empty when no checks', function () {
    $contentId = Str::uuid()->toString();

    $response = $this->getJson("/api/v1/contents/{$contentId}/safety-checks", $this->headers);

    $response->assertStatus(200);

    expect($response->json('data'))->toBeEmpty();
});

it('POST /contents/{id}/safety-check — 401 unauthenticated', function () {
    $contentId = Str::uuid()->toString();

    $response = $this->postJson("/api/v1/contents/{$contentId}/safety-check");

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org checks', function () {
    $contentId = Str::uuid()->toString();

    // Insert check for own org
    insertSafetyCheck($this->orgId, $contentId);

    // Create another org with a different user
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];

    // Insert check for other org (same content_id)
    insertSafetyCheck($otherOrgId, $contentId);

    $response = $this->getJson("/api/v1/contents/{$contentId}/safety-checks", $this->headers);

    $response->assertStatus(200);

    $ids = array_map(
        fn ($item) => $item['id'],
        $response->json('data'),
    );

    expect(count($response->json('data')))->toBe(1);
});
