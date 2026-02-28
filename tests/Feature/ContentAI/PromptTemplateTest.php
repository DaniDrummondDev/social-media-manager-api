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

    // Prompt templates require Professional plan with ai_generation_advanced
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
});

it('POST /ai/prompt-templates — 201 creates template', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/prompt-templates', [
        'generation_type' => 'title',
        'version' => 'v1',
        'name' => 'Custom Title Template',
        'system_prompt' => 'You are a social media expert.',
        'user_prompt_template' => 'Write a title for: {topic}',
        'variables' => ['topic'],
        'is_default' => false,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'prompt_template')
        ->assertJsonPath('data.attributes.generation_type', 'title')
        ->assertJsonPath('data.attributes.name', 'Custom Title Template')
        ->assertJsonPath('data.attributes.is_active', true)
        ->assertJsonPath('data.attributes.total_uses', 0);
});

it('POST /ai/prompt-templates — 422 missing required fields', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/prompt-templates', []);

    $response->assertStatus(422);
});

it('POST /ai/prompt-templates — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai/prompt-templates', [
        'generation_type' => 'title',
        'version' => 'v1',
        'name' => 'Test',
        'system_prompt' => 'sys',
        'user_prompt_template' => 'usr',
    ]);

    $response->assertStatus(401);
});
