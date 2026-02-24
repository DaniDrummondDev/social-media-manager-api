<?php

declare(strict_types=1);

use App\Domain\Publishing\ValueObjects\PublishError;

it('creates publish error', function () {
    $error = new PublishError(
        code: 'RATE_LIMITED',
        message: 'Too many requests',
        isPermanent: false,
    );

    expect($error->code)->toBe('RATE_LIMITED')
        ->and($error->message)->toBe('Too many requests')
        ->and($error->isPermanent)->toBeFalse();
});

it('converts to array', function () {
    $error = new PublishError(
        code: 'INVALID_TOKEN',
        message: 'Token expired',
        isPermanent: true,
    );

    expect($error->toArray())->toBe([
        'code' => 'INVALID_TOKEN',
        'message' => 'Token expired',
        'is_permanent' => true,
    ]);
});
