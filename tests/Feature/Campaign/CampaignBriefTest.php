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

it('creates campaign with brief fields — 201', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/campaigns', [
            'name' => 'Campaign With Brief',
            'brief_text' => 'Launch our new product line with focus on sustainability.',
            'brief_target_audience' => 'Millennials aged 25-35 interested in eco-friendly products.',
            'brief_restrictions' => 'No competitor mentions. Avoid political topics.',
            'brief_cta' => 'Visit our store and get 20% off with code ECO20.',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'campaign')
        ->assertJsonPath('data.attributes.name', 'Campaign With Brief')
        ->assertJsonPath('data.attributes.brief.text', 'Launch our new product line with focus on sustainability.')
        ->assertJsonPath('data.attributes.brief.target_audience', 'Millennials aged 25-35 interested in eco-friendly products.')
        ->assertJsonPath('data.attributes.brief.restrictions', 'No competitor mentions. Avoid political topics.')
        ->assertJsonPath('data.attributes.brief.cta', 'Visit our store and get 20% off with code ECO20.');
});

it('creates campaign without brief — 201', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/campaigns', [
            'name' => 'Campaign No Brief',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.attributes.name', 'Campaign No Brief')
        ->assertJsonPath('data.attributes.brief.text', null)
        ->assertJsonPath('data.attributes.brief.target_audience', null)
        ->assertJsonPath('data.attributes.brief.restrictions', null)
        ->assertJsonPath('data.attributes.brief.cta', null);
});

it('updates campaign brief — 200', function () {
    $createResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/campaigns', [
            'name' => 'Campaign To Update Brief',
        ]);

    $campaignId = $createResponse->json('data.id');

    $response = $this->withHeaders($this->headers)
        ->putJson("/api/v1/campaigns/{$campaignId}", [
            'brief_text' => 'Updated brief text for the campaign.',
            'brief_cta' => 'Shop now!',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', 'Updated brief text for the campaign.')
        ->assertJsonPath('data.attributes.brief.cta', 'Shop now!');
});

it('updates campaign brief merges with existing — 200', function () {
    $createResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/campaigns', [
            'name' => 'Campaign Merge Brief',
            'brief_text' => 'Original brief text.',
            'brief_target_audience' => 'Young professionals.',
            'brief_restrictions' => 'No alcohol references.',
            'brief_cta' => 'Learn more at our site.',
        ]);

    $campaignId = $createResponse->json('data.id');

    $response = $this->withHeaders($this->headers)
        ->putJson("/api/v1/campaigns/{$campaignId}", [
            'brief_cta' => 'Buy now with free shipping!',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', 'Original brief text.')
        ->assertJsonPath('data.attributes.brief.target_audience', 'Young professionals.')
        ->assertJsonPath('data.attributes.brief.restrictions', 'No alcohol references.')
        ->assertJsonPath('data.attributes.brief.cta', 'Buy now with free shipping!');
});

it('clears campaign brief with clear_brief flag — 200', function () {
    $createResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/campaigns', [
            'name' => 'Campaign Clear Brief',
            'brief_text' => 'Brief to be cleared.',
            'brief_target_audience' => 'Everyone.',
            'brief_cta' => 'Act now!',
        ]);

    $campaignId = $createResponse->json('data.id');

    $response = $this->withHeaders($this->headers)
        ->putJson("/api/v1/campaigns/{$campaignId}", [
            'clear_brief' => true,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', null)
        ->assertJsonPath('data.attributes.brief.target_audience', null)
        ->assertJsonPath('data.attributes.brief.restrictions', null)
        ->assertJsonPath('data.attributes.brief.cta', null);
});

it('clear_brief prevails over brief fields sent simultaneously — 200', function () {
    $createResponse = $this->withHeaders($this->headers)
        ->postJson('/api/v1/campaigns', [
            'name' => 'Campaign Clear Prevails',
            'brief_text' => 'Original text.',
            'brief_cta' => 'Original CTA.',
        ]);

    $campaignId = $createResponse->json('data.id');

    $response = $this->withHeaders($this->headers)
        ->putJson("/api/v1/campaigns/{$campaignId}", [
            'clear_brief' => true,
            'brief_text' => 'This should be ignored because clear_brief wins.',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', null)
        ->assertJsonPath('data.attributes.brief.target_audience', null)
        ->assertJsonPath('data.attributes.brief.restrictions', null)
        ->assertJsonPath('data.attributes.brief.cta', null);
});

it('validates brief_text max length — 422', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson('/api/v1/campaigns', [
            'name' => 'Campaign Validation Test',
            'brief_text' => str_repeat('a', 2001),
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.0.field', 'brief_text');
});
