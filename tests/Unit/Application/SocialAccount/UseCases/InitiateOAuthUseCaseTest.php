<?php

declare(strict_types=1);

use App\Application\SocialAccount\Contracts\OAuthStateServiceInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\InitiateOAuthInput;
use App\Application\SocialAccount\DTOs\InitiateOAuthOutput;
use App\Application\SocialAccount\UseCases\InitiateOAuthUseCase;
use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;

beforeEach(function () {
    $this->stateService = Mockery::mock(OAuthStateServiceInterface::class);
    $this->adapterFactory = Mockery::mock(SocialAccountAdapterFactoryInterface::class);

    $this->useCase = new InitiateOAuthUseCase(
        $this->stateService,
        $this->adapterFactory,
    );
});

it('generates authorization URL for provider', function () {
    $adapter = Mockery::mock(SocialAuthenticatorInterface::class);
    $adapter->shouldReceive('getAuthorizationUrl')
        ->once()
        ->with('state-token', [])
        ->andReturn('https://instagram.com/oauth/authorize?state=state-token');

    $this->adapterFactory->shouldReceive('make')->once()->andReturn($adapter);
    $this->stateService->shouldReceive('generateState')
        ->once()
        ->with('org-id', 'user-id', 'instagram')
        ->andReturn('state-token');

    $output = $this->useCase->execute(new InitiateOAuthInput(
        organizationId: 'org-id',
        userId: 'user-id',
        provider: 'instagram',
    ));

    expect($output)->toBeInstanceOf(InitiateOAuthOutput::class)
        ->and($output->authorizationUrl)->toBe('https://instagram.com/oauth/authorize?state=state-token')
        ->and($output->state)->toBe('state-token');
});

it('throws on invalid provider', function () {
    $this->useCase->execute(new InitiateOAuthInput(
        organizationId: 'org-id',
        userId: 'user-id',
        provider: 'invalid-provider',
    ));
})->throws(ValueError::class);
