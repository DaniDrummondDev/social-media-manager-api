<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Controllers;

use App\Application\SocialListening\DTOs\GetListeningDashboardInput;
use App\Application\SocialListening\DTOs\PlatformBreakdownOutput;
use App\Application\SocialListening\DTOs\SentimentTrendOutput;
use App\Application\SocialListening\UseCases\GetListeningDashboardUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use App\Infrastructure\SocialListening\Resources\ListeningDashboardResource;
use Illuminate\Http\JsonResponse;
use App\Infrastructure\SocialListening\Requests\GetListeningDashboardRequest;

final class ListeningDashboardController
{
    public function index(
        GetListeningDashboardRequest $request,
        GetListeningDashboardUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetListeningDashboardInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            queryId: $request->validated('query_id'),
            period: $request->validated('period') ?? '7d',
            from: $request->validated('from'),
            to: $request->validated('to'),
        ));

        return ApiResponse::success(
            ListeningDashboardResource::fromOutput($output)->toArray(),
        );
    }

    public function sentimentTrend(
        GetListeningDashboardRequest $request,
        GetListeningDashboardUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetListeningDashboardInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            queryId: $request->validated('query_id'),
            period: $request->validated('period') ?? '7d',
            from: $request->validated('from'),
            to: $request->validated('to'),
        ));

        $trend = array_map(fn (SentimentTrendOutput $item) => [
            'date' => $item->date,
            'positive' => $item->positive,
            'neutral' => $item->neutral,
            'negative' => $item->negative,
            'total' => $item->total,
        ], $output->mentionsTrend);

        return ApiResponse::success($trend);
    }

    public function platformBreakdown(
        GetListeningDashboardRequest $request,
        GetListeningDashboardUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetListeningDashboardInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            queryId: $request->validated('query_id'),
            period: $request->validated('period') ?? '7d',
            from: $request->validated('from'),
            to: $request->validated('to'),
        ));

        $breakdown = array_map(fn (PlatformBreakdownOutput $item) => [
            'platform' => $item->platform,
            'count' => $item->count,
            'percentage' => $item->percentage,
        ], $output->platformBreakdown);

        return ApiResponse::success($breakdown);
    }
}
