<?php

declare(strict_types=1);

use App\Domain\SocialListening\Exceptions\InvalidAlertConditionException;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\ConditionType;

it('creates from ConditionType, threshold, windowMinutes', function () {
    $condition = AlertCondition::create(ConditionType::VolumeSpike, 50, 60);

    expect($condition->type)->toBe(ConditionType::VolumeSpike)
        ->and($condition->threshold)->toBe(50)
        ->and($condition->windowMinutes)->toBe(60);
});

it('throws on threshold < 1', function () {
    AlertCondition::create(ConditionType::VolumeSpike, 0, 60);
})->throws(InvalidAlertConditionException::class, 'Threshold deve ser maior que zero.');

it('throws on windowMinutes < 1', function () {
    AlertCondition::create(ConditionType::VolumeSpike, 50, 0);
})->throws(InvalidAlertConditionException::class, 'Window deve ser maior que zero minutos.');

it('creates from array and converts to array', function () {
    $data = [
        'type' => 'volume_spike',
        'threshold' => 100,
        'window_minutes' => 30,
    ];

    $condition = AlertCondition::fromArray($data);

    expect($condition->type)->toBe(ConditionType::VolumeSpike)
        ->and($condition->threshold)->toBe(100)
        ->and($condition->windowMinutes)->toBe(30)
        ->and($condition->toArray())->toBe($data);
});
