<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\UseCases\EvaluateAlertsUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\Services\AlertEvaluationService;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;
use App\Domain\SocialListening\ValueObjects\Sentiment;

beforeEach(function () {
    $this->alertRepository = Mockery::mock(ListeningAlertRepositoryInterface::class);
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);
    $this->evaluationService = new AlertEvaluationService;
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new EvaluateAlertsUseCase(
        $this->alertRepository,
        $this->mentionRepository,
        $this->evaluationService,
        $this->eventDispatcher,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('evaluates alerts and triggers when volume spike condition is met', function () {
    $queryId = 'b1c2d3e4-f5a6-7890-bcde-f12345678901';

    $alert = ListeningAlert::reconstitute(
        id: Uuid::fromString('e5f6a7b8-c9d0-1234-ef00-567890123456'),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Volume Spike Alert',
        queryIds: [$queryId],
        condition: AlertCondition::create(
            type: ConditionType::VolumeSpike,
            threshold: 50,
            windowMinutes: 60,
        ),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 120,
        isActive: true,
        lastTriggeredAt: null,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $mentions = [];
    for ($i = 0; $i < 100; $i++) {
        $mentions[] = Mention::reconstitute(
            id: Uuid::generate(),
            queryId: Uuid::fromString($queryId),
            organizationId: Uuid::fromString($this->orgId),
            platform: 'instagram',
            externalId: "ext-{$i}",
            authorUsername: 'user' . $i,
            authorDisplayName: 'User ' . $i,
            authorFollowerCount: 100,
            profileUrl: null,
            content: 'Mention content',
            url: null,
            sentiment: Sentiment::Positive,
            sentimentScore: 0.85,
            reach: 100,
            engagementCount: 5,
            isFlagged: false,
            isRead: false,
            publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
            detectedAt: new DateTimeImmutable('2024-01-15T10:05:00+00:00'),
        );
    }

    $this->alertRepository->shouldReceive('findAllActive')->once()->andReturn([$alert]);
    // Current window: 100 mentions, previous window: 10 mentions => 900% spike > 50% threshold
    $this->mentionRepository->shouldReceive('countByQueryInPeriod')->twice()->andReturn(100, 10);
    $this->mentionRepository->shouldReceive('findByQueryId')->once()->andReturn($mentions);
    $this->alertRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $result = $this->useCase->execute();

    expect($result)->toBe(1);
});

it('returns zero when no alerts are active', function () {
    $this->alertRepository->shouldReceive('findAllActive')->once()->andReturn([]);

    $result = $this->useCase->execute();

    expect($result)->toBe(0);
});

it('returns zero when condition is not met', function () {
    $queryId = 'b1c2d3e4-f5a6-7890-bcde-f12345678901';

    $alert = ListeningAlert::reconstitute(
        id: Uuid::fromString('e5f6a7b8-c9d0-1234-ef00-567890123456'),
        organizationId: Uuid::fromString($this->orgId),
        name: 'Volume Spike Alert',
        queryIds: [$queryId],
        condition: AlertCondition::create(
            type: ConditionType::VolumeSpike,
            threshold: 50,
            windowMinutes: 60,
        ),
        channels: [NotificationChannel::Email],
        cooldownMinutes: 120,
        isActive: true,
        lastTriggeredAt: null,
        createdAt: new DateTimeImmutable('2024-01-01'),
        updatedAt: new DateTimeImmutable('2024-01-01'),
    );

    $this->alertRepository->shouldReceive('findAllActive')->once()->andReturn([$alert]);
    // Current window: 5 mentions, previous window: 5 mentions => 0% spike < 50% threshold
    $this->mentionRepository->shouldReceive('countByQueryInPeriod')->twice()->andReturn(5, 5);
    $this->mentionRepository->shouldReceive('findByQueryId')->once()->andReturn([]);

    $result = $this->useCase->execute();

    expect($result)->toBe(0);
});
