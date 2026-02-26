<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\RecalculateBestTimesInput;

final class RecalculateBestTimesUseCase
{
    public function execute(RecalculateBestTimesInput $input): void
    {
        // Validation only. The actual recalculation is performed
        // by CalculateBestPostingTimesJob dispatched from the controller.
        // This Use Case exists to validate input before job dispatch.
    }
}
