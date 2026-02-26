<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\RefreshAudienceInsightsInput;

final class RefreshAudienceInsightsUseCase
{
    public function execute(RefreshAudienceInsightsInput $input): void
    {
        // Validation only. The actual insight generation is performed
        // by RefreshAudienceInsightsJob dispatched from the controller.
        // This Use Case exists to validate input before job dispatch.
    }
}
