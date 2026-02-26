<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Controllers;

use App\Application\Engagement\DTOs\ListCrmSyncLogsInput;
use App\Application\Engagement\UseCases\ListCrmSyncLogsUseCase;
use App\Infrastructure\Engagement\Resources\CrmSyncLogResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CrmSyncController
{
    public function logs(
        Request $request,
        ListCrmSyncLogsUseCase $useCase,
        string $connectionId,
    ): JsonResponse {
        $logs = $useCase->execute(new ListCrmSyncLogsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            connectionId: $connectionId,
            cursor: $request->query('cursor'),
            limit: (int) ($request->query('limit') ?? 20),
        ));

        $data = array_map(
            fn ($item) => CrmSyncLogResource::fromOutput($item)->toArray(),
            $logs,
        );

        return ApiResponse::success($data);
    }
}
