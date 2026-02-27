<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\AudienceOutput;
use App\Application\PaidAdvertising\DTOs\UpdateAudienceInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\DuplicateAudienceNameException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateAudienceUseCase
{
    public function __construct(
        private readonly AudienceRepositoryInterface $audienceRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateAudienceInput $input): AudienceOutput
    {
        $audience = $this->audienceRepository->findById(Uuid::fromString($input->audienceId));

        if ($audience === null) {
            throw new AudienceNotFoundException($input->audienceId);
        }

        if ((string) $audience->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        $name = $input->name ?? $audience->name;
        $targetingSpec = $input->targetingSpec !== null
            ? TargetingSpec::fromArray($input->targetingSpec)
            : $audience->targetingSpec;

        if ($input->name !== null && $input->name !== $audience->name) {
            $organizationId = Uuid::fromString($input->organizationId);

            if ($this->audienceRepository->existsByNameAndOrganization($input->name, $organizationId)) {
                throw new DuplicateAudienceNameException($input->name);
            }
        }

        $updated = $audience->update($name, $targetingSpec, $input->userId);

        $this->audienceRepository->update($updated);
        $this->eventDispatcher->dispatch(...$updated->domainEvents);

        return AudienceOutput::fromEntity($updated);
    }
}
