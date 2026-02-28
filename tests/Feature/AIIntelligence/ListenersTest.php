<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Events\OrgStyleProfileGenerated;
use App\Domain\Analytics\Events\MetricsSynced;
use App\Domain\ContentAI\Events\PromptExperimentCompleted;
use App\Domain\Publishing\Events\PostPublished;
use App\Infrastructure\AIIntelligence\Jobs\UpdateAIGenerationContextJob;
use App\Infrastructure\AIIntelligence\Jobs\ValidatePredictionJob;
use App\Infrastructure\AIIntelligence\Listeners\ActivateWinningTemplate;
use App\Infrastructure\AIIntelligence\Listeners\SchedulePredictionValidation;
use App\Infrastructure\AIIntelligence\Listeners\TriggerPredictionValidation;
use App\Infrastructure\AIIntelligence\Listeners\UpdateGenerationContext;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();

    $this->orgId = (string) Str::uuid();
    $this->userId = (string) Str::uuid();
});

it('SchedulePredictionValidation dispatches job with delay', function () {
    $event = new PostPublished(
        aggregateId: (string) Str::uuid(),
        organizationId: $this->orgId,
        userId: $this->userId,
        contentId: (string) Str::uuid(),
        socialAccountId: (string) Str::uuid(),
        externalPostId: 'ext-123',
        publishedAt: now()->toIso8601String(),
    );

    $listener = new SchedulePredictionValidation();
    $listener->handle($event);

    Bus::assertDispatched(ValidatePredictionJob::class, function ($job) use ($event) {
        return $job->organizationId === $event->organizationId
            && $job->contentId === $event->contentId
            && $job->validationType === 'scheduled_24h';
    });
});

it('UpdateGenerationContext dispatches job on style profile generated', function () {
    $event = new OrgStyleProfileGenerated(
        aggregateId: (string) Str::uuid(),
        organizationId: $this->orgId,
        userId: $this->userId,
        generationType: 'title',
        sampleSize: 50,
        confidenceLevel: 'high',
    );

    $listener = new UpdateGenerationContext();
    $listener->handle($event);

    Bus::assertDispatched(UpdateAIGenerationContextJob::class, function ($job) use ($event) {
        return $job->organizationId === $event->organizationId
            && str_contains($job->contextType, 'style_profile');
    });
});

it('ActivateWinningTemplate is sync listener', function () {
    $listener = new ActivateWinningTemplate();

    // Verify it doesn't implement ShouldQueue
    expect($listener)->not->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('TriggerPredictionValidation is defined', function () {
    $listener = new TriggerPredictionValidation();

    expect($listener)->toBeInstanceOf(TriggerPredictionValidation::class);
});
