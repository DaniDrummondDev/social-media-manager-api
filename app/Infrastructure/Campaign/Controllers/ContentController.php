<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Controllers;

use App\Application\Campaign\DTOs\CreateContentInput;
use App\Application\Campaign\DTOs\DeleteContentInput;
use App\Application\Campaign\DTOs\UpdateContentInput;
use App\Application\Campaign\UseCases\CreateContentUseCase;
use App\Application\Campaign\UseCases\DeleteContentUseCase;
use App\Application\Campaign\UseCases\GetContentUseCase;
use App\Application\Campaign\UseCases\ListContentsUseCase;
use App\Application\Campaign\UseCases\UpdateContentUseCase;
use App\Infrastructure\Campaign\Requests\CreateContentRequest;
use App\Infrastructure\Campaign\Requests\UpdateContentRequest;
use App\Infrastructure\Campaign\Resources\ContentResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContentController
{
    public function store(
        CreateContentRequest $request,
        CreateContentUseCase $useCase,
        string $campaignId,
    ): JsonResponse {
        $output = $useCase->execute(new CreateContentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            campaignId: $campaignId,
            title: $request->validated('title'),
            body: $request->validated('body'),
            hashtags: $request->validated('hashtags', []),
            mediaIds: $request->validated('media_ids', []),
            networkOverrides: $request->validated('network_overrides', []),
        ));

        return ApiResponse::success(
            ContentResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        ListContentsUseCase $useCase,
        string $campaignId,
    ): JsonResponse {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $campaignId,
        );

        $data = array_map(
            fn ($item) => ContentResource::fromOutput($item)->toArray(),
            $output->items,
        );

        return ApiResponse::success($data);
    }

    public function show(
        Request $request,
        GetContentUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $id,
        );

        return ApiResponse::success(ContentResource::fromOutput($output)->toArray());
    }

    public function update(
        UpdateContentRequest $request,
        UpdateContentUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateContentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            contentId: $id,
            title: $request->validated('title'),
            body: $request->validated('body'),
            hashtags: $request->validated('hashtags'),
            mediaIds: $request->validated('media_ids'),
            networkOverrides: $request->validated('network_overrides'),
        ));

        return ApiResponse::success(ContentResource::fromOutput($output)->toArray());
    }

    public function destroy(
        Request $request,
        DeleteContentUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(new DeleteContentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            contentId: $id,
        ));

        return ApiResponse::success([
            'message' => 'Conteúdo excluído com sucesso.',
        ]);
    }
}
