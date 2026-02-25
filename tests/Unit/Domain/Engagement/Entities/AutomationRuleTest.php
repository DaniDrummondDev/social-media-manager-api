<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('creates with defaults', function () {
    $rule = AutomationRule::create(
        organizationId: Uuid::generate(),
        name: 'Test Rule',
        priority: 1,
        conditions: [new RuleCondition('keyword', ConditionOperator::Contains, 'hello')],
        actionType: ActionType::ReplyFixed,
        responseTemplate: 'Thanks!',
    );

    expect($rule->name)->toBe('Test Rule')
        ->and($rule->priority)->toBe(1)
        ->and($rule->isActive)->toBeTrue()
        ->and($rule->delaySeconds)->toBe(120)
        ->and($rule->dailyLimit)->toBe(100)
        ->and($rule->conditions)->toHaveCount(1)
        ->and($rule->deletedAt)->toBeNull();
});

it('updates fields', function () {
    $rule = AutomationRule::create(
        organizationId: Uuid::generate(),
        name: 'Original',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    $updated = $rule->update(name: 'Updated', priority: 2);

    expect($updated->name)->toBe('Updated')
        ->and($updated->priority)->toBe(2)
        ->and($rule->name)->toBe('Original');
});

it('activates and deactivates', function () {
    $rule = AutomationRule::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    $deactivated = $rule->deactivate();
    expect($deactivated->isActive)->toBeFalse();

    $activated = $deactivated->activate();
    expect($activated->isActive)->toBeTrue();
});

it('soft deletes', function () {
    $rule = AutomationRule::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    $deleted = $rule->softDelete();

    expect($deleted->deletedAt)->not->toBeNull()
        ->and($deleted->purgeAt)->not->toBeNull()
        ->and($deleted->isActive)->toBeFalse();
});

it('evaluates conditions with AND logic', function () {
    $rule = AutomationRule::create(
        organizationId: Uuid::generate(),
        name: 'Test',
        priority: 1,
        conditions: [
            new RuleCondition('keyword', ConditionOperator::Contains, 'great'),
            new RuleCondition('sentiment', ConditionOperator::Equals, 'positive'),
        ],
        actionType: ActionType::ReplyFixed,
    );

    $matchingComment = Comment::create(
        organizationId: Uuid::generate(),
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
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    $nonMatchingComment = Comment::create(
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-2',
        authorName: 'User',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'This is a great product!',
        sentiment: Sentiment::Negative,
        sentimentScore: 0.2,
        isFromOwner: false,
        commentedAt: new DateTimeImmutable,
    );

    expect($rule->evaluateConditions($matchingComment))->toBeTrue()
        ->and($rule->evaluateConditions($nonMatchingComment))->toBeFalse();
});

it('matches network filters', function () {
    $rule = AutomationRule::create(
        organizationId: Uuid::generate(),
        name: 'Instagram only',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
        appliesToNetworks: ['instagram'],
    );

    expect($rule->matchesFilters('instagram'))->toBeTrue()
        ->and($rule->matchesFilters('tiktok'))->toBeFalse();
});

it('matches when no filters set', function () {
    $rule = AutomationRule::create(
        organizationId: Uuid::generate(),
        name: 'All networks',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    expect($rule->matchesFilters('instagram'))->toBeTrue()
        ->and($rule->matchesFilters('tiktok'))->toBeTrue();
});
