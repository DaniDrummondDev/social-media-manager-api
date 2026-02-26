<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\GenerateListeningReportInput;
use App\Application\SocialListening\DTOs\ListeningReportOutput;
use App\Application\SocialListening\UseCases\GenerateListeningReportUseCase;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;

beforeEach(function () {
    $this->reportRepository = Mockery::mock(ListeningReportRepositoryInterface::class);

    $this->useCase = new GenerateListeningReportUseCase(
        $this->reportRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('generates a listening report successfully', function () {
    $this->reportRepository->shouldReceive('create')->once();

    $input = new GenerateListeningReportInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        queryIds: ['b1c2d3e4-f5a6-7890-bcde-f12345678901'],
        periodFrom: '2024-01-01T00:00:00+00:00',
        periodTo: '2024-01-31T23:59:59+00:00',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningReportOutput::class)
        ->and($output->organizationId)->toBe($this->orgId)
        ->and($output->queryIds)->toBe(['b1c2d3e4-f5a6-7890-bcde-f12345678901'])
        ->and($output->totalMentions)->toBe(0)
        ->and($output->status)->toBe('pending')
        ->and($output->filePath)->toBeNull()
        ->and($output->generatedAt)->toBeNull();
});
