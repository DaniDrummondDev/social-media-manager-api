<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Exceptions\InvalidCalendarItemException;
use App\Domain\AIIntelligence\ValueObjects\CalendarItem;

function createItem(array $overrides = []): CalendarItem
{
    return CalendarItem::create(
        date: $overrides['date'] ?? '2026-03-01',
        topics: $overrides['topics'] ?? ['topic-1'],
        contentType: $overrides['contentType'] ?? 'post',
        targetNetworks: $overrides['targetNetworks'] ?? ['instagram'],
        reasoning: $overrides['reasoning'] ?? 'Test reasoning',
        priority: $overrides['priority'] ?? 1,
    );
}

it('creates valid item with all fields', function () {
    $item = createItem();

    expect($item->date)->toBe('2026-03-01')
        ->and($item->topics)->toBe(['topic-1'])
        ->and($item->contentType)->toBe('post')
        ->and($item->targetNetworks)->toBe(['instagram'])
        ->and($item->reasoning)->toBe('Test reasoning')
        ->and($item->priority)->toBe(1);
});

it('throws InvalidCalendarItemException with empty topics', function () {
    createItem(['topics' => []]);
})->throws(InvalidCalendarItemException::class, 'at least one topic');

it('throws InvalidCalendarItemException with empty targetNetworks', function () {
    createItem(['targetNetworks' => []]);
})->throws(InvalidCalendarItemException::class, 'at least one target network');

it('throws InvalidCalendarItemException with priority less than 1', function () {
    createItem(['priority' => 0]);
})->throws(InvalidCalendarItemException::class, 'priority must be at least 1');

it('throws InvalidCalendarItemException with invalid date format', function () {
    createItem(['date' => '01-03-2026']);
})->throws(InvalidCalendarItemException::class, 'YYYY-MM-DD format');

it('creates from associative array via fromArray', function () {
    $item = CalendarItem::fromArray([
        'date' => '2026-03-01',
        'topics' => ['topic-1', 'topic-2'],
        'content_type' => 'reel',
        'target_networks' => ['instagram', 'tiktok'],
        'reasoning' => 'Multi-network post',
        'priority' => 3,
    ]);

    expect($item->date)->toBe('2026-03-01')
        ->and($item->topics)->toBe(['topic-1', 'topic-2'])
        ->and($item->contentType)->toBe('reel')
        ->and($item->targetNetworks)->toBe(['instagram', 'tiktok'])
        ->and($item->priority)->toBe(3);
});

it('toArray returns correct snake_case keys', function () {
    $item = createItem(['contentType' => 'reel', 'targetNetworks' => ['tiktok']]);
    $array = $item->toArray();

    expect($array)->toHaveKeys(['date', 'topics', 'content_type', 'target_networks', 'reasoning', 'priority'])
        ->and($array['content_type'])->toBe('reel')
        ->and($array['target_networks'])->toBe(['tiktok']);
});

it('toArray/fromArray roundtrip preserves data', function () {
    $original = createItem([
        'topics' => ['topic-1', 'topic-2'],
        'contentType' => 'video',
        'targetNetworks' => ['instagram', 'youtube'],
        'priority' => 5,
    ]);

    $restored = CalendarItem::fromArray($original->toArray());

    expect($restored->date)->toBe($original->date)
        ->and($restored->topics)->toBe($original->topics)
        ->and($restored->contentType)->toBe($original->contentType)
        ->and($restored->targetNetworks)->toBe($original->targetNetworks)
        ->and($restored->reasoning)->toBe($original->reasoning)
        ->and($restored->priority)->toBe($original->priority);
});
