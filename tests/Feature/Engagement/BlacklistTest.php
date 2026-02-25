<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);
});

it('lists blacklist words 200', function () {
    DB::table('automation_blacklist_words')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'word' => 'spam',
        'is_regex' => false,
        'created_at' => now()->toDateTimeString(),
    ]);

    $response = $this->getJson('/api/v1/automation-blacklist', $this->headers);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});

it('creates blacklist word 201', function () {
    $response = $this->postJson('/api/v1/automation-blacklist', [
        'word' => 'scam',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'word',
                    'is_regex',
                ],
            ],
        ]);
});

it('deletes blacklist word 204', function () {
    $wordId = (string) Str::uuid();
    DB::table('automation_blacklist_words')->insert([
        'id' => $wordId,
        'organization_id' => $this->orgId,
        'word' => 'toremove',
        'is_regex' => false,
        'created_at' => now()->toDateTimeString(),
    ]);

    $response = $this->deleteJson("/api/v1/automation-blacklist/{$wordId}", [], $this->headers);

    $response->assertStatus(204);
});

it('returns 422 on invalid regex', function () {
    $response = $this->postJson('/api/v1/automation-blacklist', [
        'word' => '[invalid',
        'is_regex' => true,
    ], $this->headers);

    $response->assertStatus(422);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/automation-blacklist');

    $response->assertStatus(401);
});
