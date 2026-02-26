<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Jobs;

use App\Application\SocialListening\DTOs\GenerateListeningReportInput;
use App\Application\SocialListening\UseCases\GenerateListeningReportUseCase;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class DispatchDailyListeningReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    public function __construct()
    {
        $this->onQueue('social-listening');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(
        ListeningQueryRepositoryInterface $queryRepository,
        GenerateListeningReportUseCase $useCase,
    ): void {
        Log::info('DispatchDailyListeningReportsJob: Starting daily report dispatch.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $yesterday = (new \DateTimeImmutable())->modify('-1 day')->format('Y-m-d');
        $today = (new \DateTimeImmutable())->format('Y-m-d');

        $orgQueryMap = $queryRepository->findActiveGroupedByOrganization();

        $dispatched = 0;

        foreach ($orgQueryMap as $orgId => $queryIds) {
            $output = $useCase->execute(new GenerateListeningReportInput(
                organizationId: $orgId,
                userId: 'system',
                queryIds: $queryIds,
                periodFrom: $yesterday,
                periodTo: $today,
            ));

            GenerateListeningReportJob::dispatch(
                $output->id,
                $orgId,
                'system',
            )->onQueue('social-listening');

            $dispatched++;
        }

        Log::info('DispatchDailyListeningReportsJob: Completed.', [
            'dispatched_reports' => $dispatched,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DispatchDailyListeningReportsJob: Failed.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
