<?php

declare(strict_types=1);

use App\Application\Shared\Contracts\SentimentAnalyzerInterface;
use App\Infrastructure\AIIntelligence\Services\PrismSentimentAnalyzer;

beforeEach(function () {
    $this->analyzer = new PrismSentimentAnalyzer();
});

it('returns neutral for empty text', function () {
    $result = $this->analyzer->analyze('');

    expect($result->sentiment)->toBe('neutral')
        ->and($result->score)->toBe(0.5);
});

it('returns neutral for whitespace only', function () {
    $result = $this->analyzer->analyze('   ');

    expect($result->sentiment)->toBe('neutral')
        ->and($result->score)->toBe(0.5);
});

it('resolves from container', function () {
    $analyzer = app(SentimentAnalyzerInterface::class);

    expect($analyzer)->toBeInstanceOf(PrismSentimentAnalyzer::class);
});
