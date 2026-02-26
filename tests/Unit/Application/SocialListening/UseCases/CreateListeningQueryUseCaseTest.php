<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\CreateListeningQueryInput;
use App\Application\SocialListening\DTOs\ListeningQueryOutput;
use App\Application\SocialListening\UseCases\CreateListeningQueryUseCase;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;

beforeEach(function () {
    $this->queryRepository = Mockery::mock(ListeningQueryRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CreateListeningQueryUseCase(
        $this->queryRepository,
        $this->eventDispatcher,
    );
});

it('creates a listening query successfully', function () {
    $this->queryRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new CreateListeningQueryInput(
        organizationId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        name: 'Brand Monitoring',
        type: 'keyword',
        value: 'social media manager',
        platforms: ['instagram', 'tiktok'],
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningQueryOutput::class)
        ->and($output->name)->toBe('Brand Monitoring')
        ->and($output->type)->toBe('keyword')
        ->and($output->value)->toBe('social media manager')
        ->and($output->platforms)->toBe(['instagram', 'tiktok'])
        ->and($output->isActive)->toBeTrue()
        ->and($output->organizationId)->toBe('f47ac10b-58cc-4372-a567-0e02b2c3d479');
});
