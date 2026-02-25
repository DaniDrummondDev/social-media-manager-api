<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\WebhookEndpoint;
use App\Domain\Engagement\Events\WebhookEndpointCreated;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates with generated secret', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Test Webhook',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    expect($endpoint->name)->toBe('Test Webhook')
        ->and($endpoint->isActive)->toBeTrue()
        ->and($endpoint->failureCount)->toBe(0)
        ->and((string) $endpoint->secret)->toStartWith('whsec_')
        ->and($endpoint->domainEvents)->toHaveCount(1)
        ->and($endpoint->domainEvents[0])->toBeInstanceOf(WebhookEndpointCreated::class);
});

it('updates without changing secret', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Original',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $originalSecret = (string) $endpoint->secret;
    $updated = $endpoint->update(name: 'Updated');

    expect($updated->name)->toBe('Updated')
        ->and((string) $updated->secret)->toBe($originalSecret);
});

it('deactivates', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $deactivated = $endpoint->deactivate();
    expect($deactivated->isActive)->toBeFalse();
});

it('records successful delivery and resets failure count', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    // Simulate some failures first
    $withFailures = $endpoint->recordDelivery(500)->recordDelivery(500);
    expect($withFailures->failureCount)->toBe(2);

    // Then a success
    $success = $withFailures->recordDelivery(200);
    expect($success->failureCount)->toBe(0)
        ->and($success->lastDeliveryStatus)->toBe(200);
});

it('increments failure count on non-2xx', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $failed = $endpoint->recordDelivery(500);
    expect($failed->failureCount)->toBe(1)
        ->and($failed->lastDeliveryStatus)->toBe(500);
});

it('auto-deactivates after 10 failures', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $current = $endpoint;
    for ($i = 0; $i < 10; $i++) {
        $current = $current->recordDelivery(500);
    }

    expect($current->shouldAutoDeactivate())->toBeTrue()
        ->and($current->failureCount)->toBe(10);
});

it('does not auto-deactivate under 10 failures', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $current = $endpoint;
    for ($i = 0; $i < 9; $i++) {
        $current = $current->recordDelivery(500);
    }

    expect($current->shouldAutoDeactivate())->toBeFalse();
});

it('soft deletes', function () {
    $endpoint = WebhookEndpoint::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $deleted = $endpoint->softDelete();

    expect($deleted->deletedAt)->not->toBeNull()
        ->and($deleted->purgeAt)->not->toBeNull()
        ->and($deleted->isActive)->toBeFalse();
});
