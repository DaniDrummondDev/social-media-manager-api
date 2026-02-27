<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\BoostOutput;
use App\Application\PaidAdvertising\DTOs\GetBoostInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetBoostUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
    ) {}

    public function execute(GetBoostInput $input): BoostOutput
    {
        $boost = $this->adBoostRepository->findById(Uuid::fromString($input->boostId));

        if ($boost === null) {
            throw new BoostNotFoundException($input->boostId);
        }

        if ((string) $boost->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        return BoostOutput::fromEntity($boost);
    }
}
