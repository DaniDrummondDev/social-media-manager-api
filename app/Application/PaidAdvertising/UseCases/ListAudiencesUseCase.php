<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\AudienceOutput;
use App\Application\PaidAdvertising\DTOs\ListAudiencesInput;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListAudiencesUseCase
{
    public function __construct(
        private readonly AudienceRepositoryInterface $audienceRepository,
    ) {}

    /**
     * @return array<AudienceOutput>
     */
    public function execute(ListAudiencesInput $input): array
    {
        $audiences = $this->audienceRepository->findByOrganizationId(
            Uuid::fromString($input->organizationId),
        );

        return array_map(
            fn ($audience) => AudienceOutput::fromEntity($audience),
            $audiences,
        );
    }
}
