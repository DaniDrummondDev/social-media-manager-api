<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\BlacklistWord;
use App\Domain\Engagement\Exceptions\InvalidBlacklistRegexException;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates a plain word', function () {
    $word = BlacklistWord::create(
        organizationId: Uuid::generate(),
        word: 'spam',
    );

    expect($word->word)->toBe('spam')
        ->and($word->isRegex)->toBeFalse();
});

it('matches plain text case-insensitively', function () {
    $word = BlacklistWord::create(
        organizationId: Uuid::generate(),
        word: 'spam',
    );

    expect($word->matches('This is SPAM content'))->toBeTrue()
        ->and($word->matches('This is good content'))->toBeFalse();
});

it('matches regex patterns', function () {
    $word = BlacklistWord::create(
        organizationId: Uuid::generate(),
        word: 'buy\s+now',
        isRegex: true,
    );

    expect($word->matches('Click to buy now!'))->toBeTrue()
        ->and($word->matches('Buy something later'))->toBeFalse();
});

it('throws on invalid regex', function () {
    BlacklistWord::create(
        organizationId: Uuid::generate(),
        word: '[invalid',
        isRegex: true,
    );
})->throws(InvalidBlacklistRegexException::class);

it('reconstitutes', function () {
    $word = BlacklistWord::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        word: 'test',
        isRegex: false,
        createdAt: new DateTimeImmutable,
    );

    expect($word->word)->toBe('test')
        ->and($word->isRegex)->toBeFalse();
});
