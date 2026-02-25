<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\BlacklistWordOutput;
use App\Application\Engagement\DTOs\CreateBlacklistWordInput;
use App\Domain\Engagement\Entities\BlacklistWord;
use App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateBlacklistWordUseCase
{
    public function __construct(
        private readonly BlacklistWordRepositoryInterface $blacklistRepository,
    ) {}

    public function execute(CreateBlacklistWordInput $input): BlacklistWordOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $word = BlacklistWord::create(
            organizationId: $organizationId,
            word: $input->word,
            isRegex: $input->isRegex,
        );

        $this->blacklistRepository->create($word);

        return BlacklistWordOutput::fromEntity($word);
    }
}
