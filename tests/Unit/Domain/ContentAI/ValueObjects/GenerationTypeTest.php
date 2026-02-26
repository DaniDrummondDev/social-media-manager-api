<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\GenerationType;

it('has all expected cases', function () {
    expect(GenerationType::cases())->toHaveCount(6);
});

it('creates from string value', function () {
    expect(GenerationType::from('title'))->toBe(GenerationType::Title)
        ->and(GenerationType::from('description'))->toBe(GenerationType::Description)
        ->and(GenerationType::from('hashtags'))->toBe(GenerationType::Hashtags)
        ->and(GenerationType::from('full_content'))->toBe(GenerationType::FullContent)
        ->and(GenerationType::from('cross_network_adaptation'))->toBe(GenerationType::CrossNetworkAdaptation)
        ->and(GenerationType::from('calendar_planning'))->toBe(GenerationType::CalendarPlanning);
});
