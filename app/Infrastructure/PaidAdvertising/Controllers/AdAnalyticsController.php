<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Controllers;

use App\Application\PaidAdvertising\DTOs\ExportSpendingReportInput;
use App\Application\PaidAdvertising\DTOs\GetAdAnalyticsOverviewInput;
use App\Application\PaidAdvertising\DTOs\GetSpendingHistoryInput;
use App\Application\PaidAdvertising\UseCases\ExportSpendingReportUseCase;
use App\Application\PaidAdvertising\UseCases\GetAdAnalyticsOverviewUseCase;
use App\Application\PaidAdvertising\UseCases\GetSpendingHistoryUseCase;
use App\Infrastructure\PaidAdvertising\Requests\ExportSpendingReportRequest;
use App\Infrastructure\PaidAdvertising\Resources\AdAnalyticsOverviewResource;
use App\Infrastructure\PaidAdvertising\Resources\SpendingHistoryResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdAnalyticsController
{
    public function overview(
        Request $request,
        GetAdAnalyticsOverviewUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetAdAnalyticsOverviewInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            from: $request->query('from'),
            to: $request->query('to'),
        ));

        return ApiResponse::success(
            AdAnalyticsOverviewResource::fromOutput($output)->toArray(),
        );
    }

    public function spending(
        Request $request,
        GetSpendingHistoryUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetSpendingHistoryInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            from: $request->query('from'),
            to: $request->query('to'),
        ));

        return ApiResponse::success(
            SpendingHistoryResource::fromOutput($output)->toArray(),
        );
    }

    public function export(
        ExportSpendingReportRequest $request,
        ExportSpendingReportUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new ExportSpendingReportInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            from: $request->validated('from'),
            to: $request->validated('to'),
            format: $request->validated('format'),
        ));

        return ApiResponse::success([
            'export_id' => $output->exportId,
            'status' => $output->status,
        ]);
    }
}
