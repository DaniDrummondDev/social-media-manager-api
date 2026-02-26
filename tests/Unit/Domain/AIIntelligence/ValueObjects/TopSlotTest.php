<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Exceptions\InvalidTimeSlotException;
use App\Domain\AIIntelligence\ValueObjects\TopSlot;

it('creates and computes dayName', function () {
    $slot = TopSlot::create(1, 9, 4.5, 30);

    expect($slot->day)->toBe(1)
        ->and($slot->dayName)->toBe('Monday')
        ->and($slot->hour)->toBe(9)
        ->and($slot->avgEngagementRate)->toBe(4.5)
        ->and($slot->sampleSize)->toBe(30);
});

it('computes Sunday for day 0', function () {
    $slot = TopSlot::create(0, 10, 3.0, 20);

    expect($slot->dayName)->toBe('Sunday');
});

it('computes Saturday for day 6', function () {
    $slot = TopSlot::create(6, 18, 5.0, 50);

    expect($slot->dayName)->toBe('Saturday');
});

it('throws on invalid day < 0', function () {
    TopSlot::create(-1, 9, 4.5, 30);
})->throws(InvalidTimeSlotException::class);

it('throws on invalid day > 6', function () {
    TopSlot::create(7, 9, 4.5, 30);
})->throws(InvalidTimeSlotException::class);

it('throws on invalid hour > 23', function () {
    TopSlot::create(1, 24, 4.5, 30);
})->throws(InvalidTimeSlotException::class);

it('round-trips via fromArray and toArray', function () {
    $original = TopSlot::create(3, 14, 4.2, 45);
    $array = $original->toArray();
    $restored = TopSlot::fromArray($array);

    expect($restored->day)->toBe($original->day)
        ->and($restored->dayName)->toBe($original->dayName)
        ->and($restored->hour)->toBe($original->hour)
        ->and($restored->avgEngagementRate)->toBe($original->avgEngagementRate)
        ->and($restored->sampleSize)->toBe($original->sampleSize);
});
