<?php

declare(strict_types=1);

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Entities\ContentNetworkOverride;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(ContentNetworkOverrideRepositoryInterface::class);
    $this->campaignRepository = app(CampaignRepositoryInterface::class);
    $this->contentRepository = app(ContentRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'override-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'override-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->campaign = Campaign::create(
        organizationId: Uuid::fromString($this->orgId),
        createdBy: Uuid::fromString($this->userId),
        name: 'Override Test '.Str::random(4),
    );
    $this->campaignRepository->create($this->campaign);

    $this->content = Content::create(
        organizationId: Uuid::fromString($this->orgId),
        campaignId: $this->campaign->id,
        createdBy: Uuid::fromString($this->userId),
        title: 'Override Content',
    );
    $this->contentRepository->create($this->content);
});

it('creates many and finds by content id', function () {
    $overrides = [
        ContentNetworkOverride::create(
            contentId: $this->content->id,
            provider: SocialProvider::Instagram,
            title: 'IG Title',
            body: 'IG Body',
        ),
        ContentNetworkOverride::create(
            contentId: $this->content->id,
            provider: SocialProvider::TikTok,
            title: 'TK Title',
        ),
    ];

    $this->repository->createMany($overrides);

    $found = $this->repository->findByContentId($this->content->id);
    expect($found)->toHaveCount(2);
});

it('deletes by content id', function () {
    $override = ContentNetworkOverride::create(
        contentId: $this->content->id,
        provider: SocialProvider::Instagram,
    );

    $this->repository->createMany([$override]);
    $this->repository->deleteByContentId($this->content->id);

    expect($this->repository->findByContentId($this->content->id))->toBeEmpty();
});

it('replaces overrides for content', function () {
    $initial = [
        ContentNetworkOverride::create(contentId: $this->content->id, provider: SocialProvider::Instagram),
    ];
    $this->repository->createMany($initial);

    $replacement = [
        ContentNetworkOverride::create(contentId: $this->content->id, provider: SocialProvider::TikTok, title: 'New TK'),
        ContentNetworkOverride::create(contentId: $this->content->id, provider: SocialProvider::YouTube, title: 'New YT'),
    ];
    $this->repository->replaceForContent($this->content->id, $replacement);

    $found = $this->repository->findByContentId($this->content->id);
    expect($found)->toHaveCount(2);
    $providers = array_map(fn ($o) => $o->provider, $found);
    expect($providers)->toContain(SocialProvider::TikTok)
        ->and($providers)->toContain(SocialProvider::YouTube);
});

it('handles empty overrides', function () {
    $this->repository->createMany([]);
    expect($this->repository->findByContentId($this->content->id))->toBeEmpty();
});
