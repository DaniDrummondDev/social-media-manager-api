<?php

declare(strict_types=1);

use App\Application\Campaign\DTOs\UpdateContentInput;
use App\Application\Campaign\UseCases\UpdateContentUseCase;
use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Exceptions\ContentNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->contentRepository = Mockery::mock(ContentRepositoryInterface::class);
    $this->overrideRepository = Mockery::mock(ContentNetworkOverrideRepositoryInterface::class);
    $this->contentMediaRepository = Mockery::mock(ContentMediaRepositoryInterface::class);

    $this->useCase = new UpdateContentUseCase(
        $this->contentRepository,
        $this->overrideRepository,
        $this->contentMediaRepository,
    );
});

it('updates content successfully', function () {
    $orgId = Uuid::generate();
    $content = Content::create(
        organizationId: $orgId,
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Original',
    );

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->contentRepository->shouldReceive('update')->once();
    $this->overrideRepository->shouldReceive('findByContentId')->once()->andReturn([]);
    $this->contentMediaRepository->shouldReceive('findByContentId')->once()->andReturn([]);

    $output = $this->useCase->execute(new UpdateContentInput(
        organizationId: (string) $orgId,
        contentId: (string) $content->id,
        title: 'Updated',
    ));

    expect($output->title)->toBe('Updated');
});

it('throws when content not found', function () {
    $this->contentRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new UpdateContentInput(
        organizationId: (string) Uuid::generate(),
        contentId: (string) Uuid::generate(),
    ));
})->throws(ContentNotFoundException::class);
