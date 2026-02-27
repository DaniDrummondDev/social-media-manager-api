<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdOAuthStateServiceInterface;
use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\DTOs\ConnectAdAccountInput;
use App\Application\PaidAdvertising\DTOs\ConnectAdAccountOutput;
use App\Application\PaidAdvertising\UseCases\ConnectAdAccountUseCase;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->stateService = Mockery::mock(AdOAuthStateServiceInterface::class);
    $this->platformFactory = Mockery::mock(AdPlatformFactoryInterface::class);

    $this->useCase = new ConnectAdAccountUseCase(
        $this->stateService,
        $this->platformFactory,
    );
});

it('generates oauth state and returns authorization url', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $adapter = Mockery::mock(AdPlatformInterface::class);
    $adapter->shouldReceive('connect')
        ->once()
        ->andReturn(['auth_url' => 'https://facebook.com/oauth', 'state' => 'test-state']);

    $this->stateService->shouldReceive('generateState')
        ->once()
        ->with($orgId, $userId, 'meta')
        ->andReturn('test-state');

    $this->platformFactory->shouldReceive('make')
        ->once()
        ->with(AdProvider::Meta)
        ->andReturn($adapter);

    $result = $this->useCase->execute(new ConnectAdAccountInput(
        organizationId: $orgId,
        userId: $userId,
        provider: 'meta',
    ));

    expect($result)->toBeInstanceOf(ConnectAdAccountOutput::class)
        ->and($result->authorizationUrl)->toBe('https://facebook.com/oauth')
        ->and($result->state)->toBe('test-state');
});

it('creates correct adapter for each provider', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $adapter = Mockery::mock(AdPlatformInterface::class);
    $adapter->shouldReceive('connect')
        ->once()
        ->andReturn(['auth_url' => 'https://ads.tiktok.com/oauth', 'state' => 'tiktok-state']);

    $this->stateService->shouldReceive('generateState')
        ->once()
        ->with($orgId, $userId, 'tiktok')
        ->andReturn('tiktok-state');

    $this->platformFactory->shouldReceive('make')
        ->once()
        ->with(AdProvider::TikTok)
        ->andReturn($adapter);

    $result = $this->useCase->execute(new ConnectAdAccountInput(
        organizationId: $orgId,
        userId: $userId,
        provider: 'tiktok',
    ));

    expect($result->authorizationUrl)->toBe('https://ads.tiktok.com/oauth')
        ->and($result->state)->toBe('tiktok-state');
});
