<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Controllers;

use App\Application\Engagement\DTOs\ListCommentsInput;
use App\Application\Engagement\DTOs\MarkAsReadInput;
use App\Application\Engagement\DTOs\ReplyCommentInput;
use App\Application\Engagement\DTOs\SuggestReplyInput;
use App\Application\Engagement\UseCases\ListCommentsUseCase;
use App\Application\Engagement\UseCases\MarkCommentAsReadUseCase;
use App\Application\Engagement\UseCases\ReplyCommentUseCase;
use App\Application\Engagement\UseCases\SuggestReplyUseCase;
use App\Infrastructure\Engagement\Requests\ListCommentsRequest;
use App\Infrastructure\Engagement\Requests\ReplyCommentRequest;
use App\Infrastructure\Engagement\Resources\CommentResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommentController
{
    public function index(
        ListCommentsRequest $request,
        ListCommentsUseCase $useCase,
    ): JsonResponse {
        $comments = $useCase->execute(new ListCommentsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            provider: $request->validated('provider'),
            campaignId: $request->validated('campaign_id'),
            contentId: $request->validated('content_id'),
            sentiment: $request->validated('sentiment'),
            isRead: $request->has('is_read') ? (bool) $request->validated('is_read') : null,
            isReplied: $request->has('is_replied') ? (bool) $request->validated('is_replied') : null,
            search: $request->validated('search'),
            from: $request->validated('from'),
            to: $request->validated('to'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        $data = array_map(
            fn ($item) => CommentResource::fromOutput($item)->toArray(),
            $comments,
        );

        return ApiResponse::success($data);
    }

    public function markAsRead(
        Request $request,
        MarkCommentAsReadUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(new MarkAsReadInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            commentIds: [$id],
        ));

        return ApiResponse::success(['marked' => 1]);
    }

    public function markManyAsRead(
        Request $request,
        MarkCommentAsReadUseCase $useCase,
    ): JsonResponse {
        $ids = $request->input('ids', []);

        $useCase->execute(new MarkAsReadInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            commentIds: $ids,
        ));

        return ApiResponse::success(['marked' => count($ids)]);
    }

    public function reply(
        ReplyCommentRequest $request,
        ReplyCommentUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new ReplyCommentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            commentId: $id,
            text: $request->validated('text'),
        ));

        return ApiResponse::success(
            CommentResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function suggestReply(
        Request $request,
        SuggestReplyUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new SuggestReplyInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            commentId: $id,
        ));

        return ApiResponse::success([
            'suggestions' => $output->suggestions,
        ]);
    }
}
