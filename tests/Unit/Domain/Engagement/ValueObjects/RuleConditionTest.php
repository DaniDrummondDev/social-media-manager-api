<?php

declare(strict_types=1);

use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Engagement\ValueObjects\Sentiment;

it('evaluates keyword contains', function () {
    $condition = new RuleCondition('keyword', ConditionOperator::Contains, 'great');

    expect($condition->evaluate('This is a great product!', null, null))->toBeTrue()
        ->and($condition->evaluate('This is bad', null, null))->toBeFalse();
});

it('evaluates keyword equals', function () {
    $condition = new RuleCondition('keyword', ConditionOperator::Equals, 'hello');

    expect($condition->evaluate('hello', null, null))->toBeTrue()
        ->and($condition->evaluate('hello world', null, null))->toBeFalse();
});

it('evaluates keyword not_contains', function () {
    $condition = new RuleCondition('keyword', ConditionOperator::NotContains, 'spam');

    expect($condition->evaluate('This is a good comment', null, null))->toBeTrue()
        ->and($condition->evaluate('This is spam content', null, null))->toBeFalse();
});

it('evaluates sentiment equals', function () {
    $condition = new RuleCondition('sentiment', ConditionOperator::Equals, 'positive');

    expect($condition->evaluate('', Sentiment::Positive, null))->toBeTrue()
        ->and($condition->evaluate('', Sentiment::Negative, null))->toBeFalse();
});

it('evaluates sentiment in', function () {
    $condition = new RuleCondition('sentiment', ConditionOperator::In, 'positive,neutral');

    expect($condition->evaluate('', Sentiment::Positive, null))->toBeTrue()
        ->and($condition->evaluate('', Sentiment::Neutral, null))->toBeTrue()
        ->and($condition->evaluate('', Sentiment::Negative, null))->toBeFalse();
});

it('evaluates author_name contains', function () {
    $condition = new RuleCondition('author_name', ConditionOperator::Contains, 'john');

    expect($condition->evaluate('', null, 'John Doe'))->toBeTrue()
        ->and($condition->evaluate('', null, 'Jane Smith'))->toBeFalse();
});

it('respects case sensitivity', function () {
    $condition = new RuleCondition('keyword', ConditionOperator::Contains, 'GREAT', isCaseSensitive: true);

    expect($condition->evaluate('This is GREAT', null, null))->toBeTrue()
        ->and($condition->evaluate('This is great', null, null))->toBeFalse();
});

it('is case insensitive by default', function () {
    $condition = new RuleCondition('keyword', ConditionOperator::Contains, 'GREAT');

    expect($condition->evaluate('This is great', null, null))->toBeTrue();
});
