<?php

declare(strict_types=1);

use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);
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
