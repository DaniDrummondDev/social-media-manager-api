<?php

declare(strict_types=1);

use App\Application\Engagement\DTOs\CreateWebhookInput;
use App\Application\Engagement\Exceptions\WebhookLimitExceededException;
use App\Application\Engagement\UseCases\CreateWebhookUseCase;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates webhook with secret', function () {
    $repo = Mockery::mock(WebhookEndpointRepositoryInterface::class);
    $repo->shouldReceive('countByOrganization')->once()->andReturn(0);
    $repo->shouldReceive('create')->once();

    $useCase = new CreateWebhookUseCase($repo);

    $output = $useCase->execute(new CreateWebhookInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        name: 'Test Webhook',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    ));

    expect($output->name)->toBe('Test Webhook')
        ->and($output->url)->toBe('https://example.com/webhook')
        ->and($output->secret)->not->toBeNull()
        ->and($output->secret)->toStartWith('whsec_')
        ->and($output->isActive)->toBeTrue()
        ->and($output->events)->toBe(['comment.created']);
});

it('throws when limit exceeded', function () {
    $repo = Mockery::mock(WebhookEndpointRepositoryInterface::class);
    $repo->shouldReceive('countByOrganization')->once()->andReturn(10);

    $useCase = new CreateWebhookUseCase($repo);

    $useCase->execute(new CreateWebhookInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        name: 'Test',
        url: 'https://example.com/webhook',
        events: ['comment.created'],
    ));
})->throws(WebhookLimitExceededException::class);
