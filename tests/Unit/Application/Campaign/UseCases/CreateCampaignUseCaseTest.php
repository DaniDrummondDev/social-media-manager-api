<?php

declare(strict_types=1);

use App\Application\Campaign\DTOs\CreateCampaignInput;
use App\Application\Campaign\UseCases\CreateCampaignUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Exceptions\DuplicateCampaignNameException;

beforeEach(function () {
    $this->campaignRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CreateCampaignUseCase(
        $this->campaignRepository,
        $this->eventDispatcher,
    );
});

it('creates campaign successfully', function () {
    $this->campaignRepository->shouldReceive('existsByOrganizationAndName')->once()->andReturn(false);
    $this->campaignRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new CreateCampaignInput(
        organizationId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        userId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        name: 'Test Campaign',
        description: 'A description',
        tags: ['test'],
    ));

    expect($output->name)->toBe('Test Campaign')
        ->and($output->status)->toBe('draft')
        ->and($output->tags)->toBe(['test']);
});

it('throws when campaign name is duplicate', function () {
    $this->campaignRepository->shouldReceive('existsByOrganizationAndName')->once()->andReturn(true);

    $this->useCase->execute(new CreateCampaignInput(
        organizationId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        userId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        name: 'Duplicate Name',
    ));
})->throws(DuplicateCampaignNameException::class);
