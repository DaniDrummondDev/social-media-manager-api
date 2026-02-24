<?php

declare(strict_types=1);

use App\Application\Media\DTOs\MediaListOutput;
use App\Application\Media\UseCases\ListMediaUseCase;
use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->mediaRepository = Mockery::mock(MediaRepositoryInterface::class);

    $this->useCase = new ListMediaUseCase($this->mediaRepository);
});

it('returns list of media for organization', function () {
    $orgId = Uuid::generate();
    $media = Media::create(
        organizationId: $orgId,
        uploadedBy: Uuid::generate(),
        fileName: 'abc.jpg',
        originalName: 'photo.jpg',
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(2 * 1024 * 1024),
        storagePath: 'orgs/org/media/abc.jpg',
        disk: 'spaces',
        checksum: hash('sha256', 'test'),
        dimensions: Dimensions::create(1080, 1080),
    );

    $this->mediaRepository->shouldReceive('findByOrganizationId')->once()->andReturn([$media]);

    $output = $this->useCase->execute((string) $orgId);

    expect($output)->toBeInstanceOf(MediaListOutput::class)
        ->and($output->items)->toHaveCount(1)
        ->and($output->items[0]->originalName)->toBe('photo.jpg');
});

it('returns empty list when no media exists', function () {
    $this->mediaRepository->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $output = $this->useCase->execute((string) Uuid::generate());

    expect($output->items)->toBeEmpty();
});
