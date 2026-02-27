<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\DeleteAudienceInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteAudienceUseCase
{
    public function __construct(
        private readonly AudienceRepositoryInterface $audienceRepository,
    ) {}

    public function execute(DeleteAudienceInput $input): void
    {
        $audienceId = Uuid::fromString($input->audienceId);
        $audience = $this->audienceRepository->findById($audienceId);

        if ($audience === null) {
            throw new AudienceNotFoundException($input->audienceId);
        }

        if ((string) $audience->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        $this->audienceRepository->delete($audienceId);
    }
}
