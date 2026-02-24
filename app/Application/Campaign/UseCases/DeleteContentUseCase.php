<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\DeleteContentInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Exceptions\ContentNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteContentUseCase
{
    public function __construct(
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteContentInput $input): void
    {
        $content = $this->contentRepository->findById(Uuid::fromString($input->contentId));

        if ($content === null || (string) $content->organizationId !== $input->organizationId || $content->isDeleted()) {
            throw new ContentNotFoundException($input->contentId);
        }

        $content = $content->softDelete();
        $this->contentRepository->update($content);
        $this->eventDispatcher->dispatch(...$content->domainEvents);
    }
}
