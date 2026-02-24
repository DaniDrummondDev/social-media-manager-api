<?php

declare(strict_types=1);

use App\Application\Campaign\DTOs\DuplicateCampaignInput;
use App\Application\Campaign\UseCases\DuplicateCampaignUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->campaignRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $this->contentRepository = Mockery::mock(ContentRepositoryInterface::class);
    $this->overrideRepository = Mockery::mock(ContentNetworkOverrideRepositoryInterface::class);
    $this->contentMediaRepository = Mockery::mock(ContentMediaRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new DuplicateCampaignUseCase(
        $this->campaignRepository,
        $this->contentRepository,
        $this->overrideRepository,
        $this->contentMediaRepository,
        $this->eventDispatcher,
    );
});

it('duplicates campaign with contents', function () {
    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $campaign = Campaign::create(organizationId: $orgId, createdBy: $userId, name: 'Original');
    $content = Content::create(
        organizationId: $orgId,
        campaignId: $campaign->id,
        createdBy: $userId,
        title: 'Content 1',
    );

    $this->campaignRepository->shouldReceive('findById')->once()->andReturn($campaign);
    $this->campaignRepository->shouldReceive('create')->once();
    $this->contentRepository->shouldReceive('findByCampaignId')->once()->andReturn([$content]);
    $this->contentRepository->shouldReceive('create')->once();
    $this->overrideRepository->shouldReceive('findByContentId')->once()->andReturn([]);
    $this->contentMediaRepository->shouldReceive('findByContentId')->once()->andReturn([]);
    $this->eventDispatcher->shouldReceive('dispatch')->atLeast()->once();

    $output = $this->useCase->execute(new DuplicateCampaignInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        campaignId: (string) $campaign->id,
    ));

    expect($output->status)->toBe('draft');
});

it('throws when campaign not found', function () {
    $this->campaignRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new DuplicateCampaignInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        campaignId: (string) Uuid::generate(),
    ));
})->throws(CampaignNotFoundException::class);
