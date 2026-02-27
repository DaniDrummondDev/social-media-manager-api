<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Controllers;

use App\Application\PaidAdvertising\DTOs\CreateAudienceInput;
use App\Application\PaidAdvertising\DTOs\DeleteAudienceInput;
use App\Application\PaidAdvertising\DTOs\GetAudienceInput;
use App\Application\PaidAdvertising\DTOs\ListAudiencesInput;
use App\Application\PaidAdvertising\DTOs\SearchInterestsInput;
use App\Application\PaidAdvertising\DTOs\UpdateAudienceInput;
use App\Application\PaidAdvertising\UseCases\CreateAudienceUseCase;
use App\Application\PaidAdvertising\UseCases\DeleteAudienceUseCase;
use App\Application\PaidAdvertising\UseCases\GetAudienceUseCase;
use App\Application\PaidAdvertising\UseCases\ListAudiencesUseCase;
use App\Application\PaidAdvertising\UseCases\SearchInterestsUseCase;
use App\Application\PaidAdvertising\UseCases\UpdateAudienceUseCase;
use App\Infrastructure\PaidAdvertising\Requests\CreateAudienceRequest;
use App\Infrastructure\PaidAdvertising\Requests\SearchInterestsRequest;
use App\Infrastructure\PaidAdvertising\Requests\UpdateAudienceRequest;
use App\Infrastructure\PaidAdvertising\Resources\AudienceResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AudienceController
{
    public function store(
        CreateAudienceRequest $request,
        CreateAudienceUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateAudienceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            name: $request->validated('name'),
            targetingSpec: $request->validated('targeting_spec'),
        ));

        return ApiResponse::success(
            AudienceResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        ListAudiencesUseCase $useCase,
    ): JsonResponse {
        $audiences = $useCase->execute(new ListAudiencesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
        ));

        $data = array_map(
            fn ($item) => AudienceResource::fromOutput($item)->toArray(),
            $audiences,
        );

        return ApiResponse::success($data);
    }

    public function show(
        Request $request,
        GetAudienceUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new GetAudienceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            audienceId: $id,
        ));

        return ApiResponse::success(
            AudienceResource::fromOutput($output)->toArray(),
        );
    }

    public function update(
        UpdateAudienceRequest $request,
        UpdateAudienceUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateAudienceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            audienceId: $id,
            name: $request->validated('name'),
            targetingSpec: $request->validated('targeting_spec'),
        ));

        return ApiResponse::success(
            AudienceResource::fromOutput($output)->toArray(),
        );
    }

    public function destroy(
        Request $request,
        DeleteAudienceUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(new DeleteAudienceInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            audienceId: $id,
        ));

        return ApiResponse::noContent();
    }

    public function searchInterests(
        SearchInterestsRequest $request,
        SearchInterestsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new SearchInterestsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            accountId: $request->validated('account_id'),
            query: $request->validated('query'),
            limit: $request->validated('limit', 25),
        ));

        return ApiResponse::success([
            'interests' => $output->interests,
        ]);
    }
}
