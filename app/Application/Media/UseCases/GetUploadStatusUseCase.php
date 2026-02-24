<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\GetUploadStatusInput;
use App\Application\Media\DTOs\UploadStatusOutput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetUploadStatusUseCase
{
    public function __construct(
        private readonly MediaUploadRepositoryInterface $uploadRepository,
    ) {}

    public function execute(GetUploadStatusInput $input): UploadStatusOutput
    {
        $upload = $this->uploadRepository->findById(Uuid::fromString($input->uploadId));

        if ($upload === null || (string) $upload->organizationId !== $input->organizationId) {
            throw new UploadNotFoundException($input->uploadId);
        }

        return UploadStatusOutput::fromEntity($upload);
    }
}
