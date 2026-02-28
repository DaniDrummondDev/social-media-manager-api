<?php

declare(strict_types=1);

use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->seed(PlanSeeder::class);

    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Generation feedback requires Professional plan with ai_learning
    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'plan_id' => PlanSeeder::PROFESSIONAL_PLAN_ID,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->startOfMonth()->toDateTimeString(),
        'current_period_end' => now()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    // Create an ai_generation record for the feedback to reference
    $this->generationId = (string) Str::uuid();
    DB::table('ai_generations')->insert([
        'id' => $this->generationId,
        'organization_id' => $this->orgId,
        'user_id' => $this->user['id'],
        'type' => 'title',
        'input' => json_encode(['prompt' => 'Write a title']),
        'output' => json_encode(['title' => 'Generated title']),
        'model_used' => 'gpt-4o',
        'tokens_input' => 100,
        'tokens_output' => 50,
        'duration_ms' => 1000,
        'cost_estimate' => 0.003,
        'created_at' => now()->toDateTimeString(),
    ]);
});

it('POST /ai/feedback — 201 accepted feedback', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/feedback', [
        'generation_id' => $this->generationId,
        'action' => 'accepted',
        'original_output' => ['title' => 'Generated title'],
        'generation_type' => 'title',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.attributes.action', 'accepted')
        ->assertJsonPath('data.attributes.generation_type', 'title');
});

it('POST /ai/feedback — 201 edited feedback with editedOutput', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/feedback', [
        'generation_id' => $this->generationId,
        'action' => 'edited',
        'original_output' => ['title' => 'Original'],
        'edited_output' => ['title' => 'Edited by user'],
        'generation_type' => 'title',
        'time_to_decision_ms' => 3000,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.attributes.action', 'edited')
        ->assertJsonPath('data.attributes.generation_type', 'title');
});

it('POST /ai/feedback — 422 missing required fields', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/feedback', []);

    $response->assertStatus(422);
});

it('POST /ai/feedback — 422 edited without edited_output', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/feedback', [
        'generation_id' => $this->generationId,
        'action' => 'edited',
        'original_output' => ['title' => 'Original'],
        'generation_type' => 'title',
    ]);

    $response->assertStatus(422);
});

it('POST /ai/feedback — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai/feedback', [
        'generation_id' => $this->generationId,
        'action' => 'accepted',
        'original_output' => ['title' => 'Test'],
    ]);

    $response->assertStatus(401);
});
