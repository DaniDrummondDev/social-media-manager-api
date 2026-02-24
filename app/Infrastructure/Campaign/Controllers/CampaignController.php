<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Controllers;

use App\Application\Campaign\DTOs\CreateCampaignInput;
use App\Application\Campaign\DTOs\DeleteCampaignInput;
use App\Application\Campaign\DTOs\DuplicateCampaignInput;
use App\Application\Campaign\DTOs\UpdateCampaignInput;
use App\Application\Campaign\UseCases\CreateCampaignUseCase;
use App\Application\Campaign\UseCases\DeleteCampaignUseCase;
use App\Application\Campaign\UseCases\DuplicateCampaignUseCase;
use App\Application\Campaign\UseCases\GetCampaignUseCase;
use App\Application\Campaign\UseCases\ListCampaignsUseCase;
use App\Application\Campaign\UseCases\RestoreCampaignUseCase;
use App\Application\Campaign\UseCases\UpdateCampaignUseCase;
use App\Infrastructure\Campaign\Requests\CreateCampaignRequest;
use App\Infrastructure\Campaign\Requests\UpdateCampaignRequest;
use App\Infrastructure\Campaign\Resources\CampaignResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CampaignController
{
    public function store(
        CreateCampaignRequest $request,
        CreateCampaignUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateCampaignInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            startsAt: $request->validated('starts_at'),
            endsAt: $request->validated('ends_at'),
            tags: $request->validated('tags', []),
        ));

        return ApiResponse::success(
            CampaignResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(Request $request, ListCampaignsUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute($request->attributes->get('auth_organization_id'));

        $data = array_map(
            fn ($item) => CampaignResource::fromOutput($item)->toArray(),
            $output->items,
        );

        return ApiResponse::success($data);
    }

    public function show(
        Request $request,
        GetCampaignUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $id,
        );

        return ApiResponse::success(CampaignResource::fromOutput($output)->toArray());
    }

    public function update(
        UpdateCampaignRequest $request,
        UpdateCampaignUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateCampaignInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            campaignId: $id,
            name: $request->validated('name'),
            description: $request->validated('description'),
            startsAt: $request->validated('starts_at'),
            endsAt: $request->validated('ends_at'),
            tags: $request->validated('tags'),
            status: $request->validated('status'),
        ));

        return ApiResponse::success(CampaignResource::fromOutput($output)->toArray());
    }

    public function destroy(
        Request $request,
        DeleteCampaignUseCase $useCase,
        string $id,
    ): JsonResponse {
        $result = $useCase->execute(new DeleteCampaignInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            campaignId: $id,
        ));

        return ApiResponse::success($result);
    }

    public function duplicate(
        Request $request,
        DuplicateCampaignUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new DuplicateCampaignInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            campaignId: $id,
            name: $request->input('name'),
        ));

        return ApiResponse::success(
            CampaignResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function restore(
        Request $request,
        RestoreCampaignUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $id,
        );

        return ApiResponse::success([
            'message' => 'Campanha restaurada com sucesso.',
        ]);
    }
}
