<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\AudienceOutput;
use App\Application\PaidAdvertising\DTOs\CreateAudienceInput;
use App\Application\PaidAdvertising\Exceptions\DuplicateAudienceNameException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateAudienceUseCase
{
    public function __construct(
        private readonly AudienceRepositoryInterface $audienceRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateAudienceInput $input): AudienceOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        if ($this->audienceRepository->existsByNameAndOrganization($input->name, $organizationId)) {
            throw new DuplicateAudienceNameException($input->name);
        }

        $targetingSpec = TargetingSpec::fromArray($input->targetingSpec);

        $audience = Audience::create(
            organizationId: $organizationId,
            name: $input->name,
            targetingSpec: $targetingSpec,
            userId: $input->userId,
        );

        $this->audienceRepository->create($audience);
        $this->eventDispatcher->dispatch(...$audience->domainEvents);

        return AudienceOutput::fromEntity($audience);
    }
}
