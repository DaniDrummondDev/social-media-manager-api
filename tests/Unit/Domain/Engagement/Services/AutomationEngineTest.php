<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Engagement\Entities\BlacklistWord;
use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\Services\AutomationEngine;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

function makeTestComment(array $overrides = []): Comment
{
    return Comment::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        contentId: $overrides['contentId'] ?? Uuid::generate(),
        socialAccountId: $overrides['socialAccountId'] ?? Uuid::generate(),
        provider: $overrides['provider'] ?? SocialProvider::Instagram,
        externalCommentId: $overrides['externalCommentId'] ?? 'ext-1',
        authorName: $overrides['authorName'] ?? 'User',
        authorExternalId: null,
        authorProfileUrl: null,
        text: $overrides['text'] ?? 'Great product!',
        sentiment: $overrides['sentiment'] ?? Sentiment::Positive,
        sentimentScore: null,
        isFromOwner: $overrides['isFromOwner'] ?? false,
        commentedAt: new DateTimeImmutable,
    );
}

function makeTestRule(array $overrides = []): AutomationRule
{
    return AutomationRule::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        name: $overrides['name'] ?? 'Rule',
        priority: $overrides['priority'] ?? 1,
        conditions: $overrides['conditions'] ?? [new RuleCondition('keyword', ConditionOperator::Contains, 'great')],
        actionType: $overrides['actionType'] ?? ActionType::ReplyFixed,
        responseTemplate: $overrides['responseTemplate'] ?? 'Thanks!',
        appliesToNetworks: $overrides['appliesToNetworks'] ?? null,
    );
}

it('returns first matching rule by priority', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment(['text' => 'This is a great awesome product!']);

    $rule1 = makeTestRule(['priority' => 2, 'conditions' => [new RuleCondition('keyword', ConditionOperator::Contains, 'awesome')]]);
    $rule2 = makeTestRule(['priority' => 1, 'conditions' => [new RuleCondition('keyword', ConditionOperator::Contains, 'great')]]);

    $match = $engine->evaluate($comment, [$rule1, $rule2], [], fn () => 0);

    expect((string) $match->id)->toBe((string) $rule2->id);
});

it('returns null when no rules match', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment(['text' => 'Normal comment']);
    $rule = makeTestRule(['conditions' => [new RuleCondition('keyword', ConditionOperator::Contains, 'unique')]]);

    $match = $engine->evaluate($comment, [$rule], [], fn () => 0);

    expect($match)->toBeNull();
});

it('blocks when blacklisted word found', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment(['text' => 'This has spam content']);
    $rule = makeTestRule(['conditions' => []]);
    $blacklist = [BlacklistWord::create(Uuid::generate(), 'spam')];

    $match = $engine->evaluate($comment, [$rule], $blacklist, fn () => 0);

    expect($match)->toBeNull();
});

it('blocks when daily limit reached', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment();
    $rule = makeTestRule(['conditions' => [new RuleCondition('keyword', ConditionOperator::Contains, 'great')]]);

    $match = $engine->evaluate($comment, [$rule], [], fn () => 100);

    expect($match)->toBeNull();
});

it('ignores owner comments', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment(['isFromOwner' => true]);
    $rule = makeTestRule(['conditions' => []]);

    $match = $engine->evaluate($comment, [$rule], [], fn () => 0);

    expect($match)->toBeNull();
});

it('ignores already replied comments', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment();
    $replied = $comment->reply('Reply', Uuid::generate());
    $rule = makeTestRule(['conditions' => []]);

    $match = $engine->evaluate($replied, [$rule], [], fn () => 0);

    expect($match)->toBeNull();
});

it('filters by network', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment(['provider' => SocialProvider::TikTok]);
    $rule = makeTestRule([
        'conditions' => [],
        'appliesToNetworks' => ['instagram'],
    ]);

    $match = $engine->evaluate($comment, [$rule], [], fn () => 0);

    expect($match)->toBeNull();
});

it('skips inactive rules', function () {
    $engine = new AutomationEngine;
    $comment = makeTestComment();
    $rule = makeTestRule(['conditions' => []])->deactivate();

    $match = $engine->evaluate($comment, [$rule], [], fn () => 0);

    expect($match)->toBeNull();
});
