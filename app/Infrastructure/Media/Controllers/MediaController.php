<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Controllers;

use App\Application\Media\DTOs\DeleteMediaInput;
use App\Application\Media\DTOs\UploadSmallMediaInput;
use App\Application\Media\UseCases\DeleteMediaUseCase;
use App\Application\Media\UseCases\ListMediaUseCase;
use App\Application\Media\UseCases\UploadSmallMediaUseCase;
use App\Infrastructure\Media\Requests\UploadSmallMediaRequest;
use App\Infrastructure\Media\Resources\MediaResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MediaController
{
    public function upload(
        UploadSmallMediaRequest $request,
        UploadSmallMediaUseCase $useCase,
    ): JsonResponse {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $output = $useCase->execute(new UploadSmallMediaInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? $file->getClientMimeType(),
            fileSize: $file->getSize(),
            contents: $file->getContent(),
            checksum: $request->validated('checksum'),
        ));

        return ApiResponse::success(MediaResource::fromOutput($output)->toArray(), status: 201);
    }

    public function list(Request $request, ListMediaUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute($request->attributes->get('auth_organization_id'));

        $data = array_map(
            fn ($item) => MediaResource::fromOutput($item)->toArray(),
            $output->items,
        );

        return ApiResponse::success($data);
    }

    public function delete(
        Request $request,
        DeleteMediaUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(new DeleteMediaInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            mediaId: $id,
        ));

        return ApiResponse::noContent();
    }
}
