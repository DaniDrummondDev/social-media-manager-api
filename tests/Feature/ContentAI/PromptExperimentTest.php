<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Create two prompt template records
    $this->variantAId = (string) Str::uuid();
    $this->variantBId = (string) Str::uuid();
    $now = now()->toDateTimeString();

    $versions = ['v1', 'v2'];
    foreach ([$this->variantAId, $this->variantBId] as $index => $id) {
        DB::table('prompt_templates')->insert([
            'id' => $id,
            'organization_id' => $this->orgId,
            'generation_type' => 'title',
            'version' => $versions[$index],
            'name' => "Template {$id}",
            'system_prompt' => 'sys',
            'user_prompt_template' => 'usr',
            'variables' => json_encode([]),
            'is_active' => true,
            'is_default' => false,
            'total_uses' => 0,
            'total_accepted' => 0,
            'total_edited' => 0,
            'total_rejected' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
});

it('POST /ai/prompt-experiments — 201 creates experiment', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/prompt-experiments', [
        'generation_type' => 'title',
        'name' => 'A/B Test Title',
        'variant_a_id' => $this->variantAId,
        'variant_b_id' => $this->variantBId,
        'traffic_split' => 0.5,
        'min_sample_size' => 50,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'prompt_experiment')
        ->assertJsonPath('data.attributes.status', 'draft')
        ->assertJsonPath('data.attributes.name', 'A/B Test Title');
});

it('POST /ai/prompt-experiments — 422 missing required fields', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/prompt-experiments', []);

    $response->assertStatus(422);
});

it('POST /ai/prompt-experiments/{id}/evaluate — 200 evaluates experiment', function () {
    // Create the experiment first
    $expId = (string) Str::uuid();
    $now = now()->toDateTimeString();

    DB::table('prompt_experiments')->insert([
        'id' => $expId,
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'name' => 'Test Experiment',
        'status' => 'running',
        'variant_a_id' => $this->variantAId,
        'variant_b_id' => $this->variantBId,
        'traffic_split' => 0.5,
        'min_sample_size' => 50,
        'variant_a_uses' => 50,
        'variant_a_accepted' => 25,
        'variant_b_uses' => 50,
        'variant_b_accepted' => 24,
        'started_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/ai/prompt-experiments/{$expId}/evaluate");

    // Low confidence → stays running (unchanged)
    $response->assertOk()
        ->assertJsonPath('data.attributes.status', 'running');
});

it('POST /ai/prompt-experiments — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai/prompt-experiments', [
        'generation_type' => 'title',
        'name' => 'Test',
        'variant_a_id' => $this->variantAId,
        'variant_b_id' => $this->variantBId,
    ]);

    $response->assertStatus(401);
});
