<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\Tone;

it('has all expected cases', function () {
    expect(Tone::cases())->toHaveCount(6);
});

it('creates from string value', function () {
    expect(Tone::from('professional'))->toBe(Tone::Professional)
        ->and(Tone::from('custom'))->toBe(Tone::Custom);
});

it('returns correct values', function () {
    expect(Tone::Professional->value)->toBe('professional')
        ->and(Tone::Casual->value)->toBe('casual')
        ->and(Tone::Fun->value)->toBe('fun')
        ->and(Tone::Informative->value)->toBe('informative')
        ->and(Tone::Inspirational->value)->toBe('inspirational')
        ->and(Tone::Custom->value)->toBe('custom');
});
