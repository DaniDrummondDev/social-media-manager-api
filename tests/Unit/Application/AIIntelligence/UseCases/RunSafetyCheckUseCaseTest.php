<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\RunSafetyCheckInput;
use App\Application\AIIntelligence\DTOs\RunSafetyCheckOutput;
use App\Application\AIIntelligence\UseCases\RunSafetyCheckUseCase;
use App\Domain\AIIntelligence\Repositories\BrandSafetyCheckRepositoryInterface;

beforeEach(function () {
    $this->checkRepository = Mockery::mock(BrandSafetyCheckRepositoryInterface::class);

    $this->useCase = new RunSafetyCheckUseCase(
        $this->checkRepository,
    );
});

it('creates pending check and returns RunSafetyCheckOutput', function () {
    $this->checkRepository->shouldReceive('create')->once();

    $input = new RunSafetyCheckInput(
        organizationId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        contentId: 'b1c2d3e4-f5a6-7890-bcde-f12345678901',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(RunSafetyCheckOutput::class)
        ->and($output->contentId)->toBe('b1c2d3e4-f5a6-7890-bcde-f12345678901')
        ->and($output->status)->toBe('pending')
        ->and($output->message)->toBeString();
});
