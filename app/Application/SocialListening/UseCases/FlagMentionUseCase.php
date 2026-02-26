<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\FlagMentionInput;
use App\Application\SocialListening\DTOs\MentionOutput;
use App\Application\SocialListening\Exceptions\MentionNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;

final class FlagMentionUseCase
{
    public function __construct(
        private readonly MentionRepositoryInterface $mentionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(FlagMentionInput $input): MentionOutput
    {
        $mentionId = Uuid::fromString($input->mentionId);
        $organizationId = Uuid::fromString($input->organizationId);

        $mention = $this->mentionRepository->findById($mentionId);

        if ($mention === null || (string) $mention->organizationId !== (string) $organizationId) {
            throw new MentionNotFoundException();
        }

        $mention = $mention->flag($input->userId);

        $this->mentionRepository->update($mention);

        $this->eventDispatcher->dispatch(...$mention->domainEvents);

        return MentionOutput::fromEntity($mention);
    }
}
