<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\AIUsage;

it('creates with all properties', function () {
    $usage = new AIUsage(
        tokensInput: 150,
        tokensOutput: 85,
        model: 'gpt-4o',
        costEstimate: 0.003,
        durationMs: 1200,
    );

    expect($usage->tokensInput)->toBe(150)
        ->and($usage->tokensOutput)->toBe(85)
        ->and($usage->model)->toBe('gpt-4o')
        ->and($usage->costEstimate)->toBe(0.003)
        ->and($usage->durationMs)->toBe(1200);
});

it('converts to array', function () {
    $usage = new AIUsage(
        tokensInput: 100,
        tokensOutput: 50,
        model: 'gpt-4o-mini',
        costEstimate: 0.001,
        durationMs: 800,
    );

    $array = $usage->toArray();

    expect($array)->toBe([
        'tokens_input' => 100,
        'tokens_output' => 50,
        'model' => 'gpt-4o-mini',
        'cost_estimate' => 0.001,
        'duration_ms' => 800,
    ]);
});
