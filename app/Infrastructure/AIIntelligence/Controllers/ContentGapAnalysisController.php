<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\GenerateGapAnalysisInput;
use App\Application\AIIntelligence\DTOs\GetGapAnalysisInput;
use App\Application\AIIntelligence\DTOs\GetGapAnalysisOpportunitiesInput;
use App\Application\AIIntelligence\DTOs\ListGapAnalysesInput;
use App\Application\AIIntelligence\UseCases\GenerateGapAnalysisUseCase;
use App\Application\AIIntelligence\UseCases\GetGapAnalysisOpportunitiesUseCase;
use App\Application\AIIntelligence\UseCases\GetGapAnalysisUseCase;
use App\Application\AIIntelligence\UseCases\ListGapAnalysesUseCase;
use App\Infrastructure\AIIntelligence\Jobs\GenerateContentGapAnalysisJob;
use App\Infrastructure\AIIntelligence\Requests\GenerateGapAnalysisRequest;
use App\Infrastructure\AIIntelligence\Requests\ListGapAnalysesRequest;
use App\Infrastructure\AIIntelligence\Resources\GapAnalysisListResource;
use App\Infrastructure\AIIntelligence\Resources\GapAnalysisOpportunitiesResource;
use App\Infrastructure\AIIntelligence\Resources\GapAnalysisResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContentGapAnalysisController
{
    public function generate(
        GenerateGapAnalysisRequest $request,
        GenerateGapAnalysisUseCase $useCase,
    ): JsonResponse {
        $organizationId = $request->attributes->get('auth_organization_id');
        $userId = $request->attributes->get('auth_user_id');

        $input = new GenerateGapAnalysisInput(
            organizationId: $organizationId,
            userId: $userId,
            competitorQueryIds: $request->validated('competitor_query_ids'),
            periodDays: $request->validated('period_days') ?? 30,
        );

        $output = $useCase->execute($input);

        GenerateContentGapAnalysisJob::dispatch(
            $output->analysisId,
            $organizationId,
            $userId,
        );

        return ApiResponse::success([
            'analysis_id' => $output->analysisId,
            'status' => $output->status,
            'message' => $output->message,
        ], status: 202);
    }

    public function index(
        ListGapAnalysesRequest $request,
        ListGapAnalysesUseCase $useCase,
    ): JsonResponse {
        $input = new ListGapAnalysesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            cursor: $request->validated('cursor'),
            limit: $request->validated('limit') ?? 20,
        );

        $result = $useCase->execute($input);

        return ApiResponse::success(
            array_map(
                fn ($item) => GapAnalysisListResource::fromOutput($item)->toArray(),
                $result['items'],
            ),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function show(
        Request $request,
        string $id,
        GetGapAnalysisUseCase $useCase,
    ): JsonResponse {
        $input = new GetGapAnalysisInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            analysisId: $id,
        );

        $output = $useCase->execute($input);

        return ApiResponse::success(
            GapAnalysisResource::fromOutput($output)->toArray(),
        );
    }

    public function opportunities(
        Request $request,
        string $id,
        GetGapAnalysisOpportunitiesUseCase $useCase,
    ): JsonResponse {
        $input = new GetGapAnalysisOpportunitiesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            analysisId: $id,
        );

        $output = $useCase->execute($input);

        return ApiResponse::success(
            GapAnalysisOpportunitiesResource::fromOutput($output)->toArray(),
        );
    }
}
