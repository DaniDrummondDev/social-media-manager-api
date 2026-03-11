<?php

declare(strict_types=1);

use App\Application\ContentAI\Services\BriefContextResolver;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Exceptions\CampaignBriefRequiredException;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;
function makeCampaignWithBrief(string $orgId, ?CampaignBrief $brief = null): Campaign
{
    return Campaign::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        createdBy: Uuid::generate(),
        name: 'Test Campaign',
        description: null,
        startsAt: null,
        endsAt: null,
        status: CampaignStatus::Draft,
        tags: [],
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
        brief: $brief,
    );
}

it('returns original topic when mode is fields_only', function () {
    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $resolver = new BriefContextResolver($repo);

    $result = $resolver->resolve('fields_only', null, 'org-id', 'My topic');

    expect($result)->toBe('My topic');
    $repo->shouldNotHaveBeenCalled();
});

it('returns brief context as topic when mode is brief_only', function () {
    $orgId = (string) Uuid::generate();
    $brief = new CampaignBrief('Black Friday campaign', 'Teens', null, null);
    $campaign = makeCampaignWithBrief($orgId, $brief);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);

    $result = $resolver->resolve('brief_only', (string) $campaign->id, $orgId, '');

    expect($result)
        ->toContain('[CAMPAIGN BRIEF]')
        ->toContain('Objective: Black Friday campaign')
        ->toContain('Target Audience: Teens');
});

it('returns brief + topic when mode is brief_and_fields', function () {
    $orgId = (string) Uuid::generate();
    $brief = new CampaignBrief('Campaign context', null, null, null);
    $campaign = makeCampaignWithBrief($orgId, $brief);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);

    $result = $resolver->resolve('brief_and_fields', (string) $campaign->id, $orgId, 'User topic here');

    expect($result)
        ->toContain('[CAMPAIGN BRIEF]')
        ->toContain('Objective: Campaign context')
        ->toContain("[USER TOPIC]\nUser topic here");
});

it('throws CampaignNotFoundException when campaign not found', function () {
    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $resolver = new BriefContextResolver($repo);

    $resolver->resolve('brief_only', (string) Uuid::generate(), (string) Uuid::generate(), '');
})->throws(CampaignNotFoundException::class);

it('throws CampaignNotFoundException when campaign belongs to another org', function () {
    $campaignOrgId = (string) Uuid::generate();
    $requestOrgId = (string) Uuid::generate();
    $campaign = makeCampaignWithBrief($campaignOrgId, new CampaignBrief('Brief', null, null, null));

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);

    $resolver->resolve('brief_only', (string) $campaign->id, $requestOrgId, '');
})->throws(CampaignNotFoundException::class);

it('throws CampaignBriefRequiredException when campaign has no brief in brief_only mode', function () {
    $orgId = (string) Uuid::generate();
    $campaign = makeCampaignWithBrief($orgId, null);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);

    $resolver->resolve('brief_only', (string) $campaign->id, $orgId, '');
})->throws(CampaignBriefRequiredException::class);

it('throws CampaignBriefRequiredException when campaign has no brief in brief_and_fields mode', function () {
    $orgId = (string) Uuid::generate();
    $campaign = makeCampaignWithBrief($orgId, null);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);

    $resolver->resolve('brief_and_fields', (string) $campaign->id, $orgId, 'Some topic');
})->throws(CampaignBriefRequiredException::class);
