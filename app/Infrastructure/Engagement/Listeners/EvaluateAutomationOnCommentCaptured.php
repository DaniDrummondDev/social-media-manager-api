<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Listeners;

use App\Application\Engagement\UseCases\EvaluateAutomationUseCase;
use App\Domain\Engagement\Events\CommentCaptured;
use App\Infrastructure\Engagement\Jobs\ExecuteAutomationJob;

final class EvaluateAutomationOnCommentCaptured
{
    public function __construct(
        private readonly EvaluateAutomationUseCase $evaluateAutomation,
    ) {}

    public function handle(CommentCaptured $event): void
    {
        $result = $this->evaluateAutomation->execute($event->aggregateId);

        if ($result !== null) {
            ExecuteAutomationJob::dispatch($result['rule_id'], $event->aggregateId)
                ->delay(now()->addSeconds($result['delay_seconds']));
        }
    }
}
