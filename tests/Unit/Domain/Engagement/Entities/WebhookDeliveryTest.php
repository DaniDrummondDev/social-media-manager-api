<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\WebhookDelivery;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates with zero attempts', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    expect($delivery->attempts)->toBe(0)
        ->and($delivery->maxAttempts)->toBe(4)
        ->and($delivery->deliveredAt)->toBeNull()
        ->and($delivery->failedAt)->toBeNull();
});

it('marks as delivered', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    $delivered = $delivery->markAsDelivered(200, '{"ok":true}', 150);

    expect($delivered->attempts)->toBe(1)
        ->and($delivered->responseStatus)->toBe(200)
        ->and($delivered->deliveredAt)->not->toBeNull()
        ->and($delivered->failedAt)->toBeNull()
        ->and($delivered->nextRetryAt)->toBeNull();
});

it('marks as failed with retry on 5xx', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    $failed = $delivery->markAsFailed(500, 'Internal error', 300);

    expect($failed->attempts)->toBe(1)
        ->and($failed->responseStatus)->toBe(500)
        ->and($failed->deliveredAt)->toBeNull()
        ->and($failed->failedAt)->toBeNull()
        ->and($failed->nextRetryAt)->not->toBeNull();
});

it('marks as permanently failed on 4xx', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    $failed = $delivery->markAsFailed(400, 'Bad request', 50);

    expect($failed->attempts)->toBe(1)
        ->and($failed->failedAt)->not->toBeNull()
        ->and($failed->nextRetryAt)->toBeNull();
});

it('should not retry on 4xx', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    $failed = $delivery->markAsFailed(404, 'Not found', 50);

    expect($failed->shouldRetry())->toBeFalse();
});

it('calculates retry delays', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    // First attempt: next retry delay for attempt 2 is 60s
    expect($delivery->nextRetryDelay())->toBe(60);

    $after1 = $delivery->markAsFailed(500, 'error', 100);
    expect($after1->nextRetryDelay())->toBe(60);

    $after2 = $after1->markAsFailed(500, 'error', 100);
    expect($after2->nextRetryDelay())->toBe(300);

    $after3 = $after2->markAsFailed(500, 'error', 100);
    expect($after3->nextRetryDelay())->toBe(1800);
});

it('should retry when not delivered and under max attempts', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    expect($delivery->shouldRetry())->toBeTrue();
});

it('should not retry when already delivered', function () {
    $delivery = WebhookDelivery::create(
        webhookEndpointId: Uuid::generate(),
        event: 'comment.created',
        payload: ['test' => true],
    );

    $delivered = $delivery->markAsDelivered(200, 'ok', 100);
    expect($delivered->shouldRetry())->toBeFalse();
});
