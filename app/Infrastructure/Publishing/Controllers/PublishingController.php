<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Controllers;

use App\Application\Publishing\DTOs\PublishNowInput;
use App\Application\Publishing\DTOs\SchedulePostInput;
use App\Application\Publishing\UseCases\PublishNowUseCase;
use App\Application\Publishing\UseCases\SchedulePostUseCase;
use App\Infrastructure\Publishing\Requests\PublishNowRequest;
use App\Infrastructure\Publishing\Requests\SchedulePostRequest;
use App\Infrastructure\Publishing\Resources\ScheduledPostResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PublishingController
{
    public function schedule(
        SchedulePostRequest $request,
        SchedulePostUseCase $useCase,
        string $contentId,
    ): JsonResponse {
        $output = $useCase->execute(new SchedulePostInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            contentId: $contentId,
            socialAccountIds: $request->validated('social_account_ids'),
            scheduledAt: $request->validated('scheduled_at'),
        ));

        $scheduledPosts = array_map(
            fn ($post) => ScheduledPostResource::fromOutput($post)->toArray(),
            $output->scheduledPosts,
        );

        return ApiResponse::success([
            'content_id' => $output->contentId,
            'scheduled_posts' => $scheduledPosts,
        ], status: 201);
    }

    public function publishNow(
        PublishNowRequest $request,
        PublishNowUseCase $useCase,
        string $contentId,
    ): JsonResponse {
        $output = $useCase->execute(new PublishNowInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            contentId: $contentId,
            socialAccountIds: $request->validated('social_account_ids'),
        ));

        $scheduledPosts = array_map(
            fn ($post) => ScheduledPostResource::fromOutput($post)->toArray(),
            $output->scheduledPosts,
        );

        return ApiResponse::success([
            'content_id' => $output->contentId,
            'scheduled_posts' => $scheduledPosts,
        ], status: 202);
    }
}
