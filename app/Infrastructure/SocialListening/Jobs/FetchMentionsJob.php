<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Jobs;

use App\Application\SocialListening\DTOs\ProcessMentionsBatchInput;
use App\Application\SocialListening\UseCases\ProcessMentionsBatchUseCase;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Infrastructure\SocialListening\Adapters\SocialListeningAdapterFactory;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FetchMentionsJob implements ShouldQueue
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
        SocialListeningAdapterFactory $adapterFactory,
        ProcessMentionsBatchUseCase $processBatchUseCase,
    ): void {
        Log::info('FetchMentionsJob: Starting mention fetch cycle.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $platforms = ['instagram', 'tiktok', 'youtube'];

        foreach ($platforms as $platform) {
            $queries = $queryRepository->findActiveByPlatform($platform);
            $adapter = $adapterFactory->make($platform);

            foreach ($queries as $query) {
                $since = (new DateTimeImmutable())->modify('-15 minutes');
                $mentionsData = $adapter->fetchMentions($query->value, $query->type, $platform, $since);

                if (count($mentionsData) > 0) {
                    $processBatchUseCase->execute(new ProcessMentionsBatchInput(
                        organizationId: (string) $query->organizationId,
                        queryId: (string) $query->id,
                        mentionsData: $mentionsData,
                    ));
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchMentionsJob: Failed.', [
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
