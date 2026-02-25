<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Controllers;

use App\Application\Engagement\DTOs\CreateBlacklistWordInput;
use App\Application\Engagement\UseCases\CreateBlacklistWordUseCase;
use App\Application\Engagement\UseCases\DeleteBlacklistWordUseCase;
use App\Application\Engagement\UseCases\ListBlacklistUseCase;
use App\Infrastructure\Engagement\Resources\BlacklistWordResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlacklistController
{
    public function index(
        Request $request,
        ListBlacklistUseCase $useCase,
    ): JsonResponse {
        $words = $useCase->execute($request->attributes->get('auth_organization_id'));

        $data = array_map(
            fn ($item) => BlacklistWordResource::fromOutput($item)->toArray(),
            $words,
        );

        return ApiResponse::success($data);
    }

    public function store(
        Request $request,
        CreateBlacklistWordUseCase $useCase,
    ): JsonResponse {
        $request->validate([
            'word' => ['required', 'string', 'max:100'],
            'is_regex' => ['sometimes', 'boolean'],
        ]);

        $output = $useCase->execute(new CreateBlacklistWordInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            word: $request->input('word'),
            isRegex: (bool) $request->input('is_regex', false),
        ));

        return ApiResponse::success(
            BlacklistWordResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function destroy(
        Request $request,
        DeleteBlacklistWordUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute($request->attributes->get('auth_organization_id'), $id);

        return ApiResponse::success(null, status: 204);
    }
}
