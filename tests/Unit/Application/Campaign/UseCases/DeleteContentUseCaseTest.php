<?php

declare(strict_types=1);

use App\Application\Campaign\DTOs\DeleteContentInput;
use App\Application\Campaign\UseCases\DeleteContentUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Exceptions\ContentNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->contentRepository = Mockery::mock(ContentRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new DeleteContentUseCase(
        $this->contentRepository,
        $this->eventDispatcher,
    );
});

it('soft deletes content successfully', function () {
    $orgId = Uuid::generate();
    $content = Content::create(
        organizationId: $orgId,
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'To Delete',
    );

    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->contentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new DeleteContentInput(
        organizationId: (string) $orgId,
        contentId: (string) $content->id,
    ));
});

it('throws when content not found', function () {
    $this->contentRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new DeleteContentInput(
        organizationId: (string) Uuid::generate(),
        contentId: (string) Uuid::generate(),
    ));
})->throws(ContentNotFoundException::class);
