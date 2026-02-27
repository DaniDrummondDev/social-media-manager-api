<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\BoostOutput;
use App\Application\PaidAdvertising\DTOs\CreateBoostInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\AdAccountNotOperationalException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateBoostUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly AudienceRepositoryInterface $audienceRepository,
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateBoostInput $input): BoostOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $userId = Uuid::fromString($input->userId);

        $adAccount = $this->adAccountRepository->findById(Uuid::fromString($input->adAccountId));

        if ($adAccount === null) {
            throw new AdAccountNotFoundException($input->adAccountId);
        }

        if ((string) $adAccount->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        if (! $adAccount->isOperational()) {
            throw new AdAccountNotOperationalException($input->adAccountId, $adAccount->status->value);
        }

        $audience = $this->audienceRepository->findById(Uuid::fromString($input->audienceId));

        if ($audience === null) {
            throw new AudienceNotFoundException($input->audienceId);
        }

        if ((string) $audience->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        $scheduledPost = $this->scheduledPostRepository->findById(Uuid::fromString($input->scheduledPostId));

        if ($scheduledPost === null || (string) $scheduledPost->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        $budget = AdBudget::create(
            $input->budgetAmountCents,
            $input->budgetCurrency,
            BudgetType::from($input->budgetType),
        );

        $budget->validateForProvider($adAccount->provider);

        $objective = AdObjective::from($input->objective);

        $boost = AdBoost::create(
            organizationId: $organizationId,
            scheduledPostId: Uuid::fromString($input->scheduledPostId),
            adAccountId: $adAccount->id,
            audienceId: $audience->id,
            budget: $budget,
            durationDays: $input->durationDays,
            objective: $objective,
            createdBy: $userId,
        );

        $this->adBoostRepository->create($boost);
        $this->eventDispatcher->dispatch(...$boost->domainEvents);

        return BoostOutput::fromEntity($boost);
    }
}
