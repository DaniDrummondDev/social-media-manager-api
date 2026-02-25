<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Controllers;

use App\Application\PlatformAdmin\DTOs\ListAuditLogInput;
use App\Application\PlatformAdmin\UseCases\ListAuditLogUseCase;
use App\Infrastructure\PlatformAdmin\Requests\ListAuditLogRequest;
use App\Infrastructure\PlatformAdmin\Resources\AuditEntryResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AdminAuditController
{
    public function index(
        ListAuditLogRequest $request,
        ListAuditLogUseCase $useCase,
    ): JsonResponse {
        $input = new ListAuditLogInput(
            action: $request->validated('action'),
            adminId: $request->validated('admin_id'),
            resourceType: $request->validated('resource_type'),
            resourceId: $request->validated('resource_id'),
            from: $request->validated('from'),
            to: $request->validated('to'),
            sort: $request->validated('sort', '-created_at'),
            perPage: (int) $request->validated('per_page', 20),
            cursor: $request->validated('cursor'),
        );

        $result = $useCase->execute($input);

        return ApiResponse::success(
            array_map(fn ($item) => AuditEntryResource::fromOutput($item)->toArray(), $result['items']),
            ['per_page' => $input->perPage, 'has_more' => $result['has_more'], 'next_cursor' => $result['next_cursor']],
        );
    }
}
