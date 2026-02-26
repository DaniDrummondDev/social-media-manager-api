<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GenerateContentProfileInput;
use App\Application\AIIntelligence\DTOs\GenerateContentProfileOutput;
use App\Application\AIIntelligence\UseCases\GenerateContentProfileUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;

beforeEach(function () {
    $this->profileRepository = Mockery::mock(ContentProfileRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->useCase = new GenerateContentProfileUseCase($this->profileRepository, $this->eventDispatcher);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('creates a profile and dispatches events', function () {
    $this->profileRepository->shouldReceive('create')
        ->once()
        ->withArgs(fn (ContentProfile $p) => (string) $p->organizationId === $this->orgId
            && $p->provider === 'instagram'
        );

    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new GenerateContentProfileInput(
        organizationId: $this->orgId,
        provider: 'instagram',
        socialAccountId: null,
        userId: 'user-1',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(GenerateContentProfileOutput::class)
        ->and($output->status)->toBe('generating')
        ->and($output->message)->toBe('Content profile generation queued.')
        ->and($output->profileId)->toBeString();
});

it('creates a profile with social account id', function () {
    $socialAccountId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

    $this->profileRepository->shouldReceive('create')
        ->once()
        ->withArgs(fn (ContentProfile $p) => (string) $p->socialAccountId === $socialAccountId);

    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new GenerateContentProfileInput(
        organizationId: $this->orgId,
        provider: 'tiktok',
        socialAccountId: $socialAccountId,
        userId: 'user-1',
    );

    $output = $this->useCase->execute($input);

    expect($output->profileId)->toBeString();
});
