<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Controllers;

use App\Application\Publishing\DTOs\CancelScheduleInput;
use App\Application\Publishing\DTOs\GetCalendarInput;
use App\Application\Publishing\DTOs\GetScheduledPostInput;
use App\Application\Publishing\DTOs\ListScheduledPostsInput;
use App\Application\Publishing\DTOs\RescheduleInput;
use App\Application\Publishing\DTOs\RetryPublishInput;
use App\Application\Publishing\UseCases\CancelScheduleUseCase;
use App\Application\Publishing\UseCases\GetCalendarUseCase;
use App\Application\Publishing\UseCases\GetScheduledPostUseCase;
use App\Application\Publishing\UseCases\ListScheduledPostsUseCase;
use App\Application\Publishing\UseCases\RescheduleUseCase;
use App\Application\Publishing\UseCases\RetryPublishUseCase;
use App\Infrastructure\Publishing\Requests\GetCalendarRequest;
use App\Infrastructure\Publishing\Requests\ListScheduledPostsRequest;
use App\Infrastructure\Publishing\Requests\RescheduleRequest;
use App\Infrastructure\Publishing\Resources\CalendarResource;
use App\Infrastructure\Publishing\Resources\ScheduledPostResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScheduledPostController
{
    public function index(
        ListScheduledPostsRequest $request,
        ListScheduledPostsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new ListScheduledPostsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            status: $request->validated('status'),
            provider: $request->validated('provider'),
            campaignId: $request->validated('campaign_id'),
            from: $request->validated('from'),
            to: $request->validated('to'),
        ));

        $data = array_map(
            fn ($item) => ScheduledPostResource::fromOutput($item)->toArray(),
            $output->items,
        );

        return ApiResponse::success($data);
    }

    public function show(
        Request $request,
        GetScheduledPostUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new GetScheduledPostInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            scheduledPostId: $id,
        ));

        return ApiResponse::success(ScheduledPostResource::fromOutput($output)->toArray());
    }

    public function calendar(
        GetCalendarRequest $request,
        GetCalendarUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetCalendarInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            month: $request->validated('month') !== null ? (int) $request->validated('month') : null,
            year: $request->validated('year') !== null ? (int) $request->validated('year') : null,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            provider: $request->validated('provider'),
            campaignId: $request->validated('campaign_id'),
        ));

        return ApiResponse::success(CalendarResource::fromOutput($output)->toArray());
    }

    public function update(
        RescheduleRequest $request,
        RescheduleUseCase $useCase,
        string $id,
    ): JsonResponse {
        $result = $useCase->execute(new RescheduleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            scheduledPostId: $id,
            scheduledAt: $request->validated('scheduled_at'),
        ));

        return ApiResponse::success($result);
    }

    public function destroy(
        Request $request,
        CancelScheduleUseCase $useCase,
        string $id,
    ): JsonResponse {
        $result = $useCase->execute(new CancelScheduleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            scheduledPostId: $id,
        ));

        return ApiResponse::success($result);
    }

    public function retry(
        Request $request,
        RetryPublishUseCase $useCase,
        string $id,
    ): JsonResponse {
        $result = $useCase->execute(new RetryPublishInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            scheduledPostId: $id,
        ));

        return ApiResponse::success($result, status: 202);
    }
}
