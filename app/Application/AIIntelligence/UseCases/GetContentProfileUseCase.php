<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\ContentProfileOutput;
use App\Application\AIIntelligence\DTOs\GetContentProfileInput;
use App\Application\AIIntelligence\Exceptions\ContentProfileNotFoundException;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetContentProfileUseCase
{
    public function __construct(
        private readonly ContentProfileRepositoryInterface $profileRepository,
    ) {}

    public function execute(GetContentProfileInput $input): ContentProfileOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $socialAccountId = $input->socialAccountId !== null
            ? Uuid::fromString($input->socialAccountId)
            : null;

        $profile = $this->profileRepository->findByOrganization(
            organizationId: $organizationId,
            provider: $input->provider,
            socialAccountId: $socialAccountId,
        );

        if ($profile === null) {
            throw new ContentProfileNotFoundException;
        }

        return ContentProfileOutput::fromEntity($profile);
    }
}
