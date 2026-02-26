<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Controllers;

use App\Application\SocialListening\DTOs\CreateListeningQueryInput;
use App\Application\SocialListening\DTOs\DeleteListeningQueryInput;
use App\Application\SocialListening\DTOs\ListListeningQueriesInput;
use App\Application\SocialListening\DTOs\PauseListeningQueryInput;
use App\Application\SocialListening\DTOs\ResumeListeningQueryInput;
use App\Application\SocialListening\DTOs\UpdateListeningQueryInput;
use App\Application\SocialListening\UseCases\CreateListeningQueryUseCase;
use App\Application\SocialListening\UseCases\DeleteListeningQueryUseCase;
use App\Application\SocialListening\UseCases\ListListeningQueriesUseCase;
use App\Application\SocialListening\UseCases\PauseListeningQueryUseCase;
use App\Application\SocialListening\UseCases\ResumeListeningQueryUseCase;
use App\Application\SocialListening\UseCases\UpdateListeningQueryUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use App\Infrastructure\SocialListening\Requests\CreateListeningQueryRequest;
use App\Infrastructure\SocialListening\Requests\ListListeningQueriesRequest;
use App\Infrastructure\SocialListening\Requests\UpdateListeningQueryRequest;
use App\Infrastructure\SocialListening\Resources\ListeningQueryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListeningQueryController
{
    public function store(
        CreateListeningQueryRequest $request,
        CreateListeningQueryUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateListeningQueryInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            type: $request->validated('type'),
            value: $request->validated('value'),
            platforms: $request->validated('platforms'),
        ));

        return ApiResponse::success(
            ListeningQueryResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        ListListeningQueriesRequest $request,
        ListListeningQueriesUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListListeningQueriesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            status: $request->validated('status'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => ListeningQueryResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function update(
        UpdateListeningQueryRequest $request,
        string $queryId,
        UpdateListeningQueryUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateListeningQueryInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            queryId: $queryId,
            name: $request->validated('name'),
            value: $request->validated('value'),
            platforms: $request->validated('platforms'),
        ));

        return ApiResponse::success(
            ListeningQueryResource::fromOutput($output)->toArray(),
        );
    }

    public function pause(
        Request $request,
        string $queryId,
        PauseListeningQueryUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new PauseListeningQueryInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            queryId: $queryId,
        ));

        return ApiResponse::success(
            ListeningQueryResource::fromOutput($output)->toArray(),
        );
    }

    public function resume(
        Request $request,
        string $queryId,
        ResumeListeningQueryUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new ResumeListeningQueryInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            queryId: $queryId,
        ));

        return ApiResponse::success(
            ListeningQueryResource::fromOutput($output)->toArray(),
        );
    }

    public function destroy(
        Request $request,
        string $queryId,
        DeleteListeningQueryUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(new DeleteListeningQueryInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            queryId: $queryId,
        ));

        return ApiResponse::noContent();
    }
}
