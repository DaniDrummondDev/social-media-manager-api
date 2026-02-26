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

function insertCalendarSuggestion(string $orgId, array $overrides = []): string
{
    $id = $overrides['id'] ?? Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('calendar_suggestions')->insert(array_merge([
        'id' => $id,
        'organization_id' => $orgId,
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-07',
        'suggestions' => json_encode([
            ['date' => '2026-03-01', 'topics' => ['topic'], 'content_type' => 'post',
                'target_networks' => ['instagram'], 'reasoning' => 'Test reasoning', 'priority' => 1],
        ]),
        'based_on' => json_encode(['analytics' => true]),
        'status' => 'generated',
        'accepted_items' => null,
        'generated_at' => $now,
        'expires_at' => now()->addDays(7)->toDateTimeString(),
        'created_at' => $now,
    ], $overrides));

    return $id;
}

it('POST /calendar/suggest — 202 with suggestion_id and status generating', function () {
    Bus::fake();

    $response = $this->postJson('/api/v1/ai-intelligence/calendar/suggest', [
        'period_start' => now()->addDay()->format('Y-m-d'),
        'period_end' => now()->addDays(7)->format('Y-m-d'),
    ], $this->headers);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'data' => [
                'suggestion_id',
                'status',
                'message',
            ],
        ]);

    expect($response->json('data.status'))->toBe('generating')
        ->and($response->json('data.suggestion_id'))->toBeString();
});

it('GET /calendar/suggestions — 200 with items array and meta.next_cursor', function () {
    insertCalendarSuggestion($this->orgId);

    $response = $this->getJson('/api/v1/ai-intelligence/calendar/suggestions', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'period_start',
                        'period_end',
                        'status',
                        'item_count',
                        'generated_at',
                        'expires_at',
                    ],
                ],
            ],
            'meta' => ['next_cursor'],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('GET /calendar/suggestions — 200 empty when no suggestions', function () {
    $response = $this->getJson('/api/v1/ai-intelligence/calendar/suggestions', $this->headers);

    $response->assertStatus(200);

    expect($response->json('data'))->toBeEmpty();
});

it('GET /calendar/suggestions/{id} — 200 with full suggestion', function () {
    $id = insertCalendarSuggestion($this->orgId);

    $response = $this->getJson("/api/v1/ai-intelligence/calendar/suggestions/{$id}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'period_start',
                    'period_end',
                    'status',
                    'suggestions',
                    'based_on',
                    'accepted_items',
                    'generated_at',
                    'expires_at',
                ],
            ],
        ]);

    expect($response->json('data.id'))->toBe($id)
        ->and($response->json('data.type'))->toBe('calendar_suggestion');
});

it('GET /calendar/suggestions/{id} — 422 when not found', function () {
    $fakeId = Str::uuid()->toString();

    $response = $this->getJson("/api/v1/ai-intelligence/calendar/suggestions/{$fakeId}", $this->headers);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'CALENDAR_SUGGESTION_NOT_FOUND');
});

it('POST /calendar/suggestions/{id}/accept — 200 with acceptance summary', function () {
    $id = insertCalendarSuggestion($this->orgId);

    $response = $this->postJson("/api/v1/ai-intelligence/calendar/suggestions/{$id}/accept", [
        'accepted_indexes' => [0],
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'accepted_count',
                'total_count',
            ],
        ]);

    expect($response->json('data.status'))->toBe('accepted')
        ->and($response->json('data.accepted_count'))->toBe(1)
        ->and($response->json('data.total_count'))->toBe(1);
});

it('POST /calendar/suggest — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai-intelligence/calendar/suggest', [
        'period_start' => now()->addDay()->format('Y-m-d'),
        'period_end' => now()->addDays(7)->format('Y-m-d'),
    ]);

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org suggestions', function () {
    // Insert suggestion for own org
    insertCalendarSuggestion($this->orgId);

    // Create another org with a different user
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];

    // Insert suggestion for other org
    insertCalendarSuggestion($otherOrgId);

    $response = $this->getJson('/api/v1/ai-intelligence/calendar/suggestions', $this->headers);

    $response->assertStatus(200);

    expect(count($response->json('data')))->toBe(1);
});
