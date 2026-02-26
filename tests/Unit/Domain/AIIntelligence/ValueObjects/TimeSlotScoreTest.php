<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Exceptions\InvalidTimeSlotException;
use App\Domain\AIIntelligence\ValueObjects\TimeSlotScore;

it('creates with valid data', function () {
    $slot = TimeSlotScore::create(1, 9, 85);

    expect($slot->day)->toBe(1)
        ->and($slot->hour)->toBe(9)
        ->and($slot->score)->toBe(85);
});

it('throws on invalid day < 0', function () {
    TimeSlotScore::create(-1, 9, 85);
})->throws(InvalidTimeSlotException::class);

it('throws on invalid day > 6', function () {
    TimeSlotScore::create(7, 9, 85);
})->throws(InvalidTimeSlotException::class);

it('throws on invalid hour < 0', function () {
    TimeSlotScore::create(1, -1, 85);
})->throws(InvalidTimeSlotException::class);

it('throws on invalid hour > 23', function () {
    TimeSlotScore::create(1, 24, 85);
})->throws(InvalidTimeSlotException::class);

it('throws on invalid score < 0', function () {
    TimeSlotScore::create(1, 9, -1);
})->throws(InvalidTimeSlotException::class);

it('throws on invalid score > 100', function () {
    TimeSlotScore::create(1, 9, 101);
})->throws(InvalidTimeSlotException::class);

it('round-trips via fromArray and toArray', function () {
    $original = TimeSlotScore::create(3, 14, 72);
    $array = $original->toArray();
    $restored = TimeSlotScore::fromArray($array);

    expect($restored->day)->toBe($original->day)
        ->and($restored->hour)->toBe($original->hour)
        ->and($restored->score)->toBe($original->score);
});
