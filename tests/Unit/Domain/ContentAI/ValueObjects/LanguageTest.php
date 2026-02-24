<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\Language;

it('has all expected cases', function () {
    expect(Language::cases())->toHaveCount(3);
});

it('creates from string value', function () {
    expect(Language::from('pt_BR'))->toBe(Language::PtBR)
        ->and(Language::from('en_US'))->toBe(Language::EnUS)
        ->and(Language::from('es_ES'))->toBe(Language::EsES);
});

it('returns correct labels', function () {
    expect(Language::PtBR->label())->toBe('Português (Brasil)')
        ->and(Language::EnUS->label())->toBe('English (US)')
        ->and(Language::EsES->label())->toBe('Español (España)');
});
