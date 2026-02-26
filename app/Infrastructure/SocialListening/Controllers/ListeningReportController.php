<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Controllers;

use App\Application\SocialListening\DTOs\GenerateListeningReportInput;
use App\Application\SocialListening\DTOs\GetListeningReportInput;
use App\Application\SocialListening\DTOs\ListListeningReportsInput;
use App\Application\SocialListening\UseCases\GenerateListeningReportUseCase;
use App\Application\SocialListening\UseCases\GetListeningReportUseCase;
use App\Application\SocialListening\UseCases\ListListeningReportsUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use App\Infrastructure\SocialListening\Requests\GenerateListeningReportRequest;
use App\Infrastructure\SocialListening\Requests\ListListeningReportsRequest;
use App\Infrastructure\SocialListening\Resources\ListeningReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListeningReportController
{
    public function store(
        GenerateListeningReportRequest $request,
        GenerateListeningReportUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GenerateListeningReportInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            queryIds: $request->validated('query_ids'),
            periodFrom: $request->validated('period_from'),
            periodTo: $request->validated('period_to'),
        ));

        return ApiResponse::success(
            ListeningReportResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        ListListeningReportsRequest $request,
        ListListeningReportsUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListListeningReportsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => ListeningReportResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function show(
        Request $request,
        string $reportId,
        GetListeningReportUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetListeningReportInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            reportId: $reportId,
        ));

        return ApiResponse::success(
            ListeningReportResource::fromOutput($output)->toArray(),
        );
    }
}
