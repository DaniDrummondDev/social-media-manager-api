<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\WebhookHttpClientInterface;
use App\Application\Engagement\UseCases\DeliverWebhookUseCase;
use App\Domain\Engagement\Entities\WebhookDelivery;
use App\Domain\Engagement\Entities\WebhookEndpoint;
use App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('delivers successfully', function () {
    $orgId = Uuid::generate();
    $endpoint = WebhookEndpoint::create(
        organizationId: $orgId,
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $delivery = WebhookDelivery::create(
        webhookEndpointId: $endpoint->id,
        event: 'comment.created',
        payload: ['test' => true],
    );

    $httpClient = Mockery::mock(WebhookHttpClientInterface::class);
    $httpClient->shouldReceive('post')->once()->andReturn([
        'status' => 200,
        'body' => '{"ok":true}',
        'time_ms' => 50,
    ]);

    $deliveryRepo = Mockery::mock(WebhookDeliveryRepositoryInterface::class);
    $deliveryRepo->shouldReceive('findById')->once()->andReturn($delivery);
    $deliveryRepo->shouldReceive('update')->once()->withArgs(function (WebhookDelivery $d) {
        return $d->deliveredAt !== null && $d->attempts === 1;
    });

    $endpointRepo = Mockery::mock(WebhookEndpointRepositoryInterface::class);
    $endpointRepo->shouldReceive('findById')->once()->andReturn($endpoint);
    $endpointRepo->shouldReceive('update')->once();

    $useCase = new DeliverWebhookUseCase($deliveryRepo, $endpointRepo, $httpClient);
    $useCase->execute((string) $delivery->id);
});

it('marks as failed with retry on 5xx', function () {
    $orgId = Uuid::generate();
    $endpoint = WebhookEndpoint::create(
        organizationId: $orgId,
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    );

    $delivery = WebhookDelivery::create(
        webhookEndpointId: $endpoint->id,
        event: 'comment.created',
        payload: ['test' => true],
    );

    $httpClient = Mockery::mock(WebhookHttpClientInterface::class);
    $httpClient->shouldReceive('post')->once()->andReturn([
        'status' => 500,
        'body' => 'Internal Error',
        'time_ms' => 100,
    ]);

    $deliveryRepo = Mockery::mock(WebhookDeliveryRepositoryInterface::class);
    $deliveryRepo->shouldReceive('findById')->once()->andReturn($delivery);
    $deliveryRepo->shouldReceive('update')->once()->withArgs(function (WebhookDelivery $d) {
        return $d->deliveredAt === null && $d->attempts === 1 && $d->nextRetryAt !== null;
    });

    $endpointRepo = Mockery::mock(WebhookEndpointRepositoryInterface::class);
    $endpointRepo->shouldReceive('findById')->once()->andReturn($endpoint);
    $endpointRepo->shouldReceive('update')->once();

    $useCase = new DeliverWebhookUseCase($deliveryRepo, $endpointRepo, $httpClient);
    $useCase->execute((string) $delivery->id);
});

it('does nothing when delivery not found', function () {
    $deliveryRepo = Mockery::mock(WebhookDeliveryRepositoryInterface::class);
    $deliveryRepo->shouldReceive('findById')->once()->andReturn(null);

    $endpointRepo = Mockery::mock(WebhookEndpointRepositoryInterface::class);
    $httpClient = Mockery::mock(WebhookHttpClientInterface::class);

    $useCase = new DeliverWebhookUseCase($deliveryRepo, $endpointRepo, $httpClient);
    $useCase->execute((string) Uuid::generate());

    expect(true)->toBeTrue();
});

it('does nothing when endpoint is inactive', function () {
    $orgId = Uuid::generate();
    $endpoint = WebhookEndpoint::create(
        organizationId: $orgId,
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    )->deactivate();

    $delivery = WebhookDelivery::create(
        webhookEndpointId: $endpoint->id,
        event: 'comment.created',
        payload: ['test' => true],
    );

    $deliveryRepo = Mockery::mock(WebhookDeliveryRepositoryInterface::class);
    $deliveryRepo->shouldReceive('findById')->once()->andReturn($delivery);

    $endpointRepo = Mockery::mock(WebhookEndpointRepositoryInterface::class);
    $endpointRepo->shouldReceive('findById')->once()->andReturn($endpoint);

    $httpClient = Mockery::mock(WebhookHttpClientInterface::class);

    $useCase = new DeliverWebhookUseCase($deliveryRepo, $endpointRepo, $httpClient);
    $useCase->execute((string) $delivery->id);

    expect(true)->toBeTrue();
});
