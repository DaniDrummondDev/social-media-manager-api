<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Controllers;

use App\Application\Analytics\DTOs\ExportReportInput;
use App\Application\Analytics\DTOs\GetContentAnalyticsInput;
use App\Application\Analytics\DTOs\GetExportInput;
use App\Application\Analytics\DTOs\GetNetworkAnalyticsInput;
use App\Application\Analytics\DTOs\GetOverviewInput;
use App\Application\Analytics\DTOs\ListExportsInput;
use App\Application\Analytics\UseCases\ExportReportUseCase;
use App\Application\Analytics\UseCases\GetContentAnalyticsUseCase;
use App\Application\Analytics\UseCases\GetExportUseCase;
use App\Application\Analytics\UseCases\GetNetworkAnalyticsUseCase;
use App\Application\Analytics\UseCases\GetOverviewUseCase;
use App\Application\Analytics\UseCases\ListExportsUseCase;
use App\Infrastructure\Analytics\Jobs\GenerateReportJob;
use App\Infrastructure\Analytics\Requests\ExportReportRequest;
use App\Infrastructure\Analytics\Requests\GetAnalyticsRequest;
use App\Infrastructure\Analytics\Resources\ContentAnalyticsResource;
use App\Infrastructure\Analytics\Resources\ExportResource;
use App\Infrastructure\Analytics\Resources\NetworkAnalyticsResource;
use App\Infrastructure\Analytics\Resources\OverviewResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AnalyticsController
{
    public function overview(
        GetAnalyticsRequest $request,
        GetOverviewUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetOverviewInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            period: $request->validated('period'),
            from: $request->validated('from'),
            to: $request->validated('to'),
        ));

        return ApiResponse::success(
            OverviewResource::fromOutput($output)->toArray(),
        );
    }

    public function network(
        GetAnalyticsRequest $request,
        GetNetworkAnalyticsUseCase $useCase,
        string $provider,
    ): JsonResponse {
        $output = $useCase->execute(new GetNetworkAnalyticsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $provider,
            period: $request->validated('period'),
            from: $request->validated('from'),
            to: $request->validated('to'),
        ));

        return ApiResponse::success(
            NetworkAnalyticsResource::fromOutput($output)->toArray(),
        );
    }

    public function content(
        Request $request,
        GetContentAnalyticsUseCase $useCase,
        string $contentId,
    ): JsonResponse {
        $output = $useCase->execute(new GetContentAnalyticsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            contentId: $contentId,
        ));

        return ApiResponse::success(
            ContentAnalyticsResource::fromOutput($output)->toArray(),
        );
    }

    public function export(
        ExportReportRequest $request,
        ExportReportUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new ExportReportInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            type: $request->validated('type'),
            format: $request->validated('format'),
            period: $request->validated('period'),
            from: $request->validated('from'),
            to: $request->validated('to'),
            filterProvider: $request->validated('filter_provider'),
            filterCampaignId: $request->validated('filter_campaign_id'),
            filterContentId: $request->validated('filter_content_id'),
        ));

        GenerateReportJob::dispatch($output->exportId);

        return ApiResponse::success(
            ExportResource::fromOutput($output)->toArray(),
            status: 202,
        );
    }

    public function showExport(
        Request $request,
        GetExportUseCase $useCase,
        string $exportId,
    ): JsonResponse {
        $output = $useCase->execute(new GetExportInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            exportId: $exportId,
        ));

        return ApiResponse::success(
            ExportResource::fromOutput($output)->toArray(),
        );
    }

    public function listExports(
        Request $request,
        ListExportsUseCase $useCase,
    ): JsonResponse {
        $exports = $useCase->execute(new ListExportsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
        ));

        $data = array_map(
            fn ($item) => ExportResource::fromOutput($item)->toArray(),
            $exports,
        );

        return ApiResponse::success($data);
    }
}
