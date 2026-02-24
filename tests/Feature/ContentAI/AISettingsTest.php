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

it('returns default settings — 200', function () {
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/ai/settings');

    $response->assertOk()
        ->assertJsonPath('data.default_tone', 'professional')
        ->assertJsonPath('data.default_language', 'pt_BR')
        ->assertJsonPath('data.monthly_generation_limit', 500)
        ->assertJsonStructure(['data' => ['usage_this_month']]);
});

it('updates settings — 200', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/ai/settings', [
        'default_tone' => 'casual',
        'default_language' => 'en_US',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.default_tone', 'casual')
        ->assertJsonPath('data.default_language', 'en_US');
});

it('validates custom tone requires description — 422', function () {
    $response = $this->withHeaders($this->headers)->putJson('/api/v1/ai/settings', [
        'default_tone' => 'custom',
    ]);

    $response->assertStatus(422);
});

it('returns history — 200', function () {
    $response = $this->withHeaders($this->headers)->getJson('/api/v1/ai/history');

    $response->assertOk()
        ->assertJsonStructure(['data']);
});

it('returns history filtered by type — 200', function () {
    // Seed a generation
    DB::table('ai_generations')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'user_id' => $this->user['id'],
        'type' => 'title',
        'input' => json_encode(['topic' => 'test']),
        'output' => json_encode(['suggestions' => []]),
        'model_used' => 'gpt-4o',
        'tokens_input' => 100,
        'tokens_output' => 50,
        'cost_estimate' => 0.002,
        'duration_ms' => 1000,
        'created_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/ai/history?type=title');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
