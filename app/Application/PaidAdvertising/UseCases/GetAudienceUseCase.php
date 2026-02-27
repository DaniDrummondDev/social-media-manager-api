<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\AudienceOutput;
use App\Application\PaidAdvertising\DTOs\GetAudienceInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetAudienceUseCase
{
    public function __construct(
        private readonly AudienceRepositoryInterface $audienceRepository,
    ) {}

    public function execute(GetAudienceInput $input): AudienceOutput
    {
        $audience = $this->audienceRepository->findById(Uuid::fromString($input->audienceId));

        if ($audience === null) {
            throw new AudienceNotFoundException($input->audienceId);
        }

        if ((string) $audience->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        return AudienceOutput::fromEntity($audience);
    }
}
