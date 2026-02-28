<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Services;

use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException;
use App\Infrastructure\Shared\Exceptions\AiAgentsRequestException;
use App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class LangGraphClient implements LangGraphClientInterface
{
    public function __construct(
        private readonly AiAgentsCircuitBreakerInterface $circuitBreaker,
    ) {}

    /**
     * Dispatch a pipeline request to the ai-agents microservice and wait for the result.
     *
     * @param  array<string, mixed>  $payload
     * @return array{result: array<string, mixed>, metadata: array<string, mixed>}
     *
     * @throws AiAgentsCircuitOpenException
     * @throws AiAgentsTimeoutException
     * @throws AiAgentsRequestException
     */
    public function dispatch(string $pipeline, array $payload): array
    {
        if ($this->circuitBreaker->isOpen($pipeline)) {
            throw new AiAgentsCircuitOpenException($pipeline);
        }

        $baseUrl = (string) config('ai-agents.base_url', 'http://ai-agents:8000');
        $secret = (string) config('ai-agents.internal_secret', '');
        $correlationId = request()?->header('X-Correlation-ID') ?? (string) Str::uuid();

        $pipelineSlug = str_replace('_', '-', $pipeline);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Internal-Secret' => $secret,
                    'X-Correlation-ID' => $correlationId,
                ])
                ->post("{$baseUrl}/api/v1/pipelines/{$pipelineSlug}", array_merge($payload, [
                    'correlation_id' => $correlationId,
                    'callback_url' => $this->buildCallbackUrl(),
                ]));
        } catch (\Throwable $e) {
            $this->circuitBreaker->recordFailure($pipeline);

            throw new AiAgentsRequestException($pipeline, $e->getMessage());
        }

        if ($response->status() !== 202) {
            $this->circuitBreaker->recordFailure($pipeline);

            throw new AiAgentsRequestException($pipeline, "Unexpected status: {$response->status()}");
        }

        /** @var string $jobId */
        $jobId = $response->json('job_id', '');

        return $this->pollForResult($pipeline, $jobId, $baseUrl, $secret);
    }

    /**
     * @return array{result: array<string, mixed>, metadata: array<string, mixed>}
     */
    private function pollForResult(string $pipeline, string $jobId, string $baseUrl, string $secret): array
    {
        $timeoutSeconds = (int) config('ai-agents.poll_timeout', 120);
        $intervalMs = (int) config('ai-agents.poll_interval_ms', 500);
        $deadline = time() + $timeoutSeconds;

        while (time() < $deadline) {
            usleep($intervalMs * 1000);

            try {
                $status = Http::timeout(10)
                    ->withHeaders(['X-Internal-Secret' => $secret])
                    ->get("{$baseUrl}/api/v1/jobs/{$jobId}");
            } catch (\Throwable) {
                continue;
            }

            if (! $status->successful()) {
                continue;
            }

            $jobStatus = $status->json('status');

            if ($jobStatus === 'completed') {
                $this->circuitBreaker->recordSuccess($pipeline);

                return [
                    'result' => (array) $status->json('result', []),
                    'metadata' => (array) $status->json('metadata', []),
                ];
            }

            if ($jobStatus === 'failed') {
                $this->circuitBreaker->recordFailure($pipeline);
                $error = $status->json('error', 'Pipeline execution failed');

                throw new AiAgentsRequestException($pipeline, (string) $error);
            }
        }

        $this->circuitBreaker->recordFailure($pipeline);

        throw new AiAgentsTimeoutException($pipeline, $jobId);
    }

    private function buildCallbackUrl(): string
    {
        /** @var string $callbackBase */
        $callbackBase = config('ai-agents.callback_base_url', 'http://nginx:80/api/v1/internal');

        return "{$callbackBase}/agent-callback";
    }
}
