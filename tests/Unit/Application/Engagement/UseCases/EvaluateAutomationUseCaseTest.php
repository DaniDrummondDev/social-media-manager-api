<?php

declare(strict_types=1);

use App\Application\Engagement\Exceptions\CommentNotFoundException;
use App\Application\Engagement\UseCases\EvaluateAutomationUseCase;
use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\Repositories\AutomationExecutionRepositoryInterface;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\Services\AutomationEngine;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

function makeComment(Uuid $orgId): Comment
{
    return Comment::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-1',
        authorName: 'User',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'This is a great product!',
        sentiment: Sentiment::Positive,
        sentimentScore: 0.9,
        isRead: false,
        isFromOwner: false,
        repliedAt: null,
        repliedBy: null,
        repliedByAutomation: false,
        replyText: null,
        replyExternalId: null,
        commentedAt: new DateTimeImmutable,
        capturedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('returns matching rule with delay', function () {
    $orgId = Uuid::generate();
    $comment = makeComment($orgId);

    $rule = AutomationRule::create(
        organizationId: $orgId,
        name: 'Test Rule',
        priority: 1,
        conditions: [new RuleCondition('keyword', ConditionOperator::Contains, 'great')],
        actionType: ActionType::ReplyFixed,
        responseTemplate: 'Thanks!',
    );

    $commentRepo = Mockery::mock(CommentRepositoryInterface::class);
    $commentRepo->shouldReceive('findById')->once()->andReturn($comment);

    $ruleRepo = Mockery::mock(AutomationRuleRepositoryInterface::class);
    $ruleRepo->shouldReceive('findActiveByOrganizationId')->once()->andReturn([$rule]);

    $executionRepo = Mockery::mock(AutomationExecutionRepositoryInterface::class);
    $executionRepo->shouldReceive('countTodayByRule')->andReturn(0);

    $blacklistRepo = Mockery::mock(BlacklistWordRepositoryInterface::class);
    $blacklistRepo->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $engine = new AutomationEngine;

    $useCase = new EvaluateAutomationUseCase(
        $commentRepo, $ruleRepo, $executionRepo, $blacklistRepo, $engine,
    );

    $result = $useCase->execute((string) $comment->id);

    expect($result)->not->toBeNull()
        ->and($result['rule_id'])->toBe((string) $rule->id)
        ->and($result['delay_seconds'])->toBe(120);
});

it('returns null when no rule matches', function () {
    $orgId = Uuid::generate();
    $comment = makeComment($orgId);

    $rule = AutomationRule::create(
        organizationId: $orgId,
        name: 'Test Rule',
        priority: 1,
        conditions: [new RuleCondition('keyword', ConditionOperator::Contains, 'unique-word')],
        actionType: ActionType::ReplyFixed,
    );

    $commentRepo = Mockery::mock(CommentRepositoryInterface::class);
    $commentRepo->shouldReceive('findById')->once()->andReturn($comment);

    $ruleRepo = Mockery::mock(AutomationRuleRepositoryInterface::class);
    $ruleRepo->shouldReceive('findActiveByOrganizationId')->once()->andReturn([$rule]);

    $executionRepo = Mockery::mock(AutomationExecutionRepositoryInterface::class);

    $blacklistRepo = Mockery::mock(BlacklistWordRepositoryInterface::class);
    $blacklistRepo->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $engine = new AutomationEngine;

    $useCase = new EvaluateAutomationUseCase(
        $commentRepo, $ruleRepo, $executionRepo, $blacklistRepo, $engine,
    );

    $result = $useCase->execute((string) $comment->id);

    expect($result)->toBeNull();
});

it('throws when comment not found', function () {
    $commentRepo = Mockery::mock(CommentRepositoryInterface::class);
    $commentRepo->shouldReceive('findById')->once()->andReturn(null);

    $ruleRepo = Mockery::mock(AutomationRuleRepositoryInterface::class);
    $executionRepo = Mockery::mock(AutomationExecutionRepositoryInterface::class);
    $blacklistRepo = Mockery::mock(BlacklistWordRepositoryInterface::class);
    $engine = new AutomationEngine;

    $useCase = new EvaluateAutomationUseCase(
        $commentRepo, $ruleRepo, $executionRepo, $blacklistRepo, $engine,
    );

    $useCase->execute((string) Uuid::generate());
})->throws(CommentNotFoundException::class);
