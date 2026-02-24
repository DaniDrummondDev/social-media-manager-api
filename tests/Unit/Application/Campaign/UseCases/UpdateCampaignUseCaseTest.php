<?php

declare(strict_types=1);

use App\Application\Campaign\DTOs\UpdateCampaignInput;
use App\Application\Campaign\UseCases\UpdateCampaignUseCase;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Campaign\Exceptions\DuplicateCampaignNameException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->campaignRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $this->useCase = new UpdateCampaignUseCase($this->campaignRepository);
});

function createCampaignForUpdate(Uuid $orgId): Campaign
{
    return Campaign::create(
        organizationId: $orgId,
        createdBy: Uuid::generate(),
        name: 'Original Name',
    );
}

it('updates campaign successfully', function () {
    $orgId = Uuid::generate();
    $campaign = createCampaignForUpdate($orgId);

    $this->campaignRepository->shouldReceive('findById')->once()->andReturn($campaign);
    $this->campaignRepository->shouldReceive('existsByOrganizationAndName')->once()->andReturn(false);
    $this->campaignRepository->shouldReceive('update')->once();

    $output = $this->useCase->execute(new UpdateCampaignInput(
        organizationId: (string) $orgId,
        campaignId: (string) $campaign->id,
        name: 'Updated Name',
    ));

    expect($output->name)->toBe('Updated Name');
});

it('throws when campaign not found', function () {
    $this->campaignRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new UpdateCampaignInput(
        organizationId: (string) Uuid::generate(),
        campaignId: (string) Uuid::generate(),
    ));
})->throws(CampaignNotFoundException::class);

it('throws when name is duplicate', function () {
    $orgId = Uuid::generate();
    $campaign = createCampaignForUpdate($orgId);

    $this->campaignRepository->shouldReceive('findById')->once()->andReturn($campaign);
    $this->campaignRepository->shouldReceive('existsByOrganizationAndName')->once()->andReturn(true);

    $this->useCase->execute(new UpdateCampaignInput(
        organizationId: (string) $orgId,
        campaignId: (string) $campaign->id,
        name: 'Conflicting Name',
    ));
})->throws(DuplicateCampaignNameException::class);
