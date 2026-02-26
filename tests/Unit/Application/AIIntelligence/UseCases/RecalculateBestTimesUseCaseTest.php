<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\RecalculateBestTimesInput;
use App\Application\AIIntelligence\UseCases\RecalculateBestTimesUseCase;

it('executes without error', function () {
    $useCase = new RecalculateBestTimesUseCase;

    $input = new RecalculateBestTimesInput(
        organizationId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        provider: 'instagram',
        socialAccountId: null,
    );

    $useCase->execute($input);

    expect(true)->toBeTrue();
});
