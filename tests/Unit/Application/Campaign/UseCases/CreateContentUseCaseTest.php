<?php

declare(strict_types=1);

use App\Application\Campaign\DTOs\CreateContentInput;
use App\Application\Campaign\UseCases\CreateContentUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->campaignRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $this->contentRepository = Mockery::mock(ContentRepositoryInterface::class);
    $this->overrideRepository = Mockery::mock(ContentNetworkOverrideRepositoryInterface::class);
    $this->contentMediaRepository = Mockery::mock(ContentMediaRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CreateContentUseCase(
        $this->campaignRepository,
        $this->contentRepository,
        $this->overrideRepository,
        $this->contentMediaRepository,
        $this->eventDispatcher,
    );
});

it('creates content successfully', function () {
    $orgId = Uuid::generate();
    $campaign = Campaign::create(organizationId: $orgId, createdBy: Uuid::generate(), name: 'Test');

    $this->campaignRepository->shouldReceive('findById')->once()->andReturn($campaign);
    $this->contentRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new CreateContentInput(
        organizationId: (string) $orgId,
        userId: (string) Uuid::generate(),
        campaignId: (string) $campaign->id,
        title: 'Test Content',
        body: 'Content body',
        hashtags: ['test'],
        mediaIds: [],
        networkOverrides: [],
    ));

    expect($output->title)->toBe('Test Content')
        ->and($output->status)->toBe('draft');
});

it('throws when campaign not found', function () {
    $this->campaignRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new CreateContentInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        campaignId: (string) Uuid::generate(),
        title: 'Test',
    ));
})->throws(CampaignNotFoundException::class);

it('throws when campaign belongs to different org', function () {
    $campaign = Campaign::create(
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: 'Other Org Campaign',
    );

    $this->campaignRepository->shouldReceive('findById')->once()->andReturn($campaign);

    $this->useCase->execute(new CreateContentInput(
        organizationId: (string) Uuid::generate(), // different org
        userId: (string) Uuid::generate(),
        campaignId: (string) $campaign->id,
        title: 'Test',
    ));
})->throws(CampaignNotFoundException::class);
