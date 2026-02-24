<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Controllers;

use App\Application\Media\DTOs\AbortUploadInput;
use App\Application\Media\DTOs\CompleteUploadInput;
use App\Application\Media\DTOs\GetUploadStatusInput;
use App\Application\Media\DTOs\InitiateUploadInput;
use App\Application\Media\DTOs\UploadChunkInput;
use App\Application\Media\UseCases\AbortUploadUseCase;
use App\Application\Media\UseCases\CompleteUploadUseCase;
use App\Application\Media\UseCases\GetUploadStatusUseCase;
use App\Application\Media\UseCases\InitiateUploadUseCase;
use App\Application\Media\UseCases\UploadChunkUseCase;
use App\Infrastructure\Media\Requests\CompleteUploadRequest;
use App\Infrastructure\Media\Requests\InitiateUploadRequest;
use App\Infrastructure\Media\Requests\UploadChunkRequest;
use App\Infrastructure\Media\Resources\ChunkReceivedResource;
use App\Infrastructure\Media\Resources\InitiateUploadResource;
use App\Infrastructure\Media\Resources\MediaResource;
use App\Infrastructure\Media\Resources\UploadStatusResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MediaUploadController
{
    public function initiate(
        InitiateUploadRequest $request,
        InitiateUploadUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new InitiateUploadInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            fileName: $request->validated('file_name'),
            mimeType: $request->validated('mime_type'),
            totalBytes: (int) $request->validated('total_bytes'),
            chunkSizeBytes: $request->validated('chunk_size_bytes')
                ? (int) $request->validated('chunk_size_bytes')
                : null,
        ));

        return ApiResponse::success(InitiateUploadResource::fromOutput($output)->toArray(), status: 201);
    }

    public function uploadChunk(
        UploadChunkRequest $request,
        UploadChunkUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new UploadChunkInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            uploadId: $id,
            chunkIndex: (int) $request->validated('chunk_index'),
            data: $request->validated('data'),
        ));

        return ApiResponse::success(ChunkReceivedResource::fromOutput($output)->toArray());
    }

    public function status(
        Request $request,
        GetUploadStatusUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new GetUploadStatusInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            uploadId: $id,
        ));

        return ApiResponse::success(UploadStatusResource::fromOutput($output)->toArray());
    }

    public function complete(
        CompleteUploadRequest $request,
        CompleteUploadUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new CompleteUploadInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            uploadId: $id,
            checksum: $request->validated('checksum'),
        ));

        return ApiResponse::success(MediaResource::fromOutput($output)->toArray(), status: 201);
    }

    public function abort(
        Request $request,
        AbortUploadUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute(new AbortUploadInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            uploadId: $id,
        ));

        return ApiResponse::noContent();
    }
}
