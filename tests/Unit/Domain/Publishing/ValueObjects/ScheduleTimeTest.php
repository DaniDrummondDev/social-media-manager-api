<?php

declare(strict_types=1);

use App\Domain\Publishing\ValueObjects\ScheduleTime;

it('creates for future with valid time', function () {
    $future = new DateTimeImmutable('+1 hour');
    $scheduleTime = ScheduleTime::forFuture($future);

    expect($scheduleTime->toDateTimeImmutable())->toBe($future);
});

it('rejects time less than 5 minutes in the future', function () {
    $tooSoon = new DateTimeImmutable('+2 minutes');

    ScheduleTime::forFuture($tooSoon);
})->throws(InvalidArgumentException::class, 'at least 5 minutes');

it('accepts custom minimum minutes', function () {
    $time = new DateTimeImmutable('+3 minutes');

    $scheduleTime = ScheduleTime::forFuture($time, minMinutes: 2);

    expect($scheduleTime->toDateTimeImmutable())->toBe($time);
});

it('creates for immediate publish', function () {
    $before = new DateTimeImmutable;
    $scheduleTime = ScheduleTime::forImmediate();
    $after = new DateTimeImmutable;

    expect($scheduleTime->toDateTimeImmutable() >= $before)->toBeTrue()
        ->and($scheduleTime->toDateTimeImmutable() <= $after)->toBeTrue();
});

it('reconstitutes from DateTimeImmutable', function () {
    $dateTime = new DateTimeImmutable('2026-06-15T10:00:00Z');
    $scheduleTime = ScheduleTime::fromDateTimeImmutable($dateTime);

    expect($scheduleTime->toDateTimeImmutable())->toBe($dateTime);
});
