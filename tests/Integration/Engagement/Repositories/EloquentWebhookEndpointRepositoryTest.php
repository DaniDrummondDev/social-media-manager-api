<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\WebhookEndpoint;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Repositories\EloquentWebhookEndpointRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->userId = (string) Str::uuid();
    $this->orgId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test',
        'email' => 'test-'.Str::random(6).'@example.com',
        'password' => 'hashed',
        'timezone' => 'UTC',
        'email_verified_at' => now()->toDateTimeString(),
        'two_factor_enabled' => false,
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'test-'.Str::random(4),
        'timezone' => 'UTC',
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates and finds by id', function () {
    $repo = app(EloquentWebhookEndpointRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    $endpoint = WebhookEndpoint::create(
        organizationId: $orgId,
        name: 'Test Webhook',
        url: 'https://example.com/webhook',
        events: ['comment.created', 'comment.replied'],
    );

    $repo->create($endpoint);

    $found = $repo->findById($endpoint->id);

    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('Test Webhook')
        ->and($found->url)->toBe('https://example.com/webhook')
        ->and($found->events)->toBe(['comment.created', 'comment.replied'])
        ->and($found->isActive)->toBeTrue()
        ->and((string) $found->secret)->toStartWith('whsec_');
});

it('finds subscribed to event', function () {
    $repo = app(EloquentWebhookEndpointRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    $endpoint1 = WebhookEndpoint::create(
        organizationId: $orgId,
        name: 'Comment Webhook',
        url: 'https://example.com/comments',
        events: ['comment.created'],
    );

    $endpoint2 = WebhookEndpoint::create(
        organizationId: $orgId,
        name: 'Reply Webhook',
        url: 'https://example.com/replies',
        events: ['comment.replied'],
    );

    $repo->create($endpoint1);
    $repo->create($endpoint2);

    $subscribed = $repo->findSubscribedToEvent($orgId, 'comment.created');

    expect($subscribed)->toHaveCount(1)
        ->and($subscribed[0]->name)->toBe('Comment Webhook');
});

it('counts by organization', function () {
    $repo = app(EloquentWebhookEndpointRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 1; $i <= 3; $i++) {
        $endpoint = WebhookEndpoint::create(
            organizationId: $orgId,
            name: "Webhook {$i}",
            url: "https://example.com/wh{$i}",
            events: ['comment.created'],
        );
        $repo->create($endpoint);
    }

    expect($repo->countByOrganization($orgId))->toBe(3);
});

it('excludes soft-deleted from count', function () {
    $repo = app(EloquentWebhookEndpointRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    $endpoint = WebhookEndpoint::create(
        organizationId: $orgId,
        name: 'To Delete',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $repo->create($endpoint);
    expect($repo->countByOrganization($orgId))->toBe(1);

    $deleted = $endpoint->softDelete();
    $repo->update($deleted);

    expect($repo->countByOrganization($orgId))->toBe(0);
});
