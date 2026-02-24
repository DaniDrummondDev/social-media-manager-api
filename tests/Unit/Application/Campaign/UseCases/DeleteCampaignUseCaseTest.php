<?php

declare(strict_types=1);

use App\Application\Campaign\DTOs\DeleteCampaignInput;
use App\Application\Campaign\UseCases\DeleteCampaignUseCase;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->campaignRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $this->useCase = new DeleteCampaignUseCase($this->campaignRepository);
});

it('soft deletes campaign successfully', function () {
    $orgId = Uuid::generate();
    $campaign = Campaign::create(
        organizationId: $orgId,
        createdBy: Uuid::generate(),
        name: 'To Delete',
    );

    $this->campaignRepository->shouldReceive('findById')->once()->andReturn($campaign);
    $this->campaignRepository->shouldReceive('update')->once();

    $result = $this->useCase->execute(new DeleteCampaignInput(
        organizationId: (string) $orgId,
        campaignId: (string) $campaign->id,
    ));

    expect($result)->toHaveKeys(['cancelled_schedules', 'purge_at'])
        ->and($result['cancelled_schedules'])->toBe(0);
});

it('throws when campaign not found', function () {
    $this->campaignRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new DeleteCampaignInput(
        organizationId: (string) Uuid::generate(),
        campaignId: (string) Uuid::generate(),
    ));
})->throws(CampaignNotFoundException::class);
