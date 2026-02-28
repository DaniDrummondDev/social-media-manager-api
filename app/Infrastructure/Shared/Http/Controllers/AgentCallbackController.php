<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Http\Controllers;

use App\Infrastructure\Shared\Http\Requests\AgentCallbackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class AgentCallbackController
{
    public function handle(AgentCallbackRequest $request): JsonResponse
    {
        /** @var string $jobId */
        $jobId = $request->validated('job_id');

        /** @var string $status */
        $status = $request->validated('status');

        /** @var string $correlationId */
        $correlationId = $request->validated('correlation_id');

        /** @var array<string, mixed>|null $result */
        $result = $request->validated('result');

        /** @var array<string, mixed>|null $metadata */
        $metadata = $request->validated('metadata');

        // Store callback result in Redis for the LangGraphClient to pick up
        Cache::store('redis')->put(
            "agent_callback:{$jobId}",
            json_encode([
                'status' => $status,
                'result' => $result,
                'metadata' => $metadata,
            ]),
            600, // 10 minutes TTL
        );

        Log::info('AI Agent callback received', [
            'correlation_id' => $correlationId,
            'job_id' => $jobId,
            'status' => $status,
            'total_tokens' => $metadata['total_tokens'] ?? null,
            'total_cost' => $metadata['total_cost'] ?? null,
            'agents_used' => $metadata['agents_used'] ?? null,
            'duration_ms' => $metadata['duration_ms'] ?? null,
        ]);

        return response()->json(['status' => 'accepted'], 202);
    }
}
