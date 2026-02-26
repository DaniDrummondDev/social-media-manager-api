<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GenerateContentProfileInput;
use App\Application\AIIntelligence\DTOs\GenerateContentProfileOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GenerateContentProfileUseCase
{
    public function __construct(
        private readonly ContentProfileRepositoryInterface $profileRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(GenerateContentProfileInput $input): GenerateContentProfileOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $socialAccountId = $input->socialAccountId !== null
            ? Uuid::fromString($input->socialAccountId)
            : null;

        $profile = ContentProfile::create(
            organizationId: $organizationId,
            socialAccountId: $socialAccountId,
            provider: $input->provider,
            userId: $input->userId,
        );

        $this->profileRepository->create($profile);
        $this->eventDispatcher->dispatch(...$profile->domainEvents);

        return new GenerateContentProfileOutput(
            profileId: (string) $profile->id,
            status: $profile->status->value,
            message: 'Content profile generation queued.',
        );
    }
}
