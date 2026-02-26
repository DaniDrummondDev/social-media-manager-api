<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Controllers;

use App\Application\SocialListening\DTOs\FlagMentionInput;
use App\Application\SocialListening\DTOs\GetMentionDetailsInput;
use App\Application\SocialListening\DTOs\ListMentionsInput;
use App\Application\SocialListening\DTOs\MarkMentionsReadInput;
use App\Application\SocialListening\DTOs\UnflagMentionInput;
use App\Application\SocialListening\UseCases\FlagMentionUseCase;
use App\Application\SocialListening\UseCases\GetMentionDetailsUseCase;
use App\Application\SocialListening\UseCases\ListMentionsUseCase;
use App\Application\SocialListening\UseCases\MarkMentionsReadUseCase;
use App\Application\SocialListening\UseCases\UnflagMentionUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use App\Infrastructure\SocialListening\Requests\ListMentionsRequest;
use App\Infrastructure\SocialListening\Requests\MarkMentionsReadRequest;
use App\Infrastructure\SocialListening\Resources\MentionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MentionController
{
    public function index(
        ListMentionsRequest $request,
        ListMentionsUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListMentionsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            queryId: $request->validated('query_id'),
            platform: $request->validated('platform'),
            sentiment: $request->validated('sentiment'),
            isFlagged: $request->has('is_flagged') ? (bool) $request->validated('is_flagged') : null,
            isRead: $request->has('is_read') ? (bool) $request->validated('is_read') : null,
            from: $request->validated('from'),
            to: $request->validated('to'),
            search: $request->validated('search'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => MentionResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function show(
        Request $request,
        string $mentionId,
        GetMentionDetailsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetMentionDetailsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            mentionId: $mentionId,
        ));

        return ApiResponse::success(
            MentionResource::fromOutput($output)->toArray(),
        );
    }

    public function flag(
        Request $request,
        string $mentionId,
        FlagMentionUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new FlagMentionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            mentionId: $mentionId,
        ));

        return ApiResponse::success(
            MentionResource::fromOutput($output)->toArray(),
        );
    }

    public function unflag(
        Request $request,
        string $mentionId,
        UnflagMentionUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new UnflagMentionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            mentionId: $mentionId,
        ));

        return ApiResponse::success(
            MentionResource::fromOutput($output)->toArray(),
        );
    }

    public function markRead(
        MarkMentionsReadRequest $request,
        MarkMentionsReadUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(new MarkMentionsReadInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            mentionIds: $request->validated('mention_ids'),
        ));

        return ApiResponse::success(['marked' => count($request->validated('mention_ids'))]);
    }
}
