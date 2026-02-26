<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\Shared\Contracts\SentimentAnalyzerInterface;
use App\Application\SocialListening\Exceptions\MentionNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\Sentiment;

final class AnalyzeMentionSentimentUseCase
{
    public function __construct(
        private readonly MentionRepositoryInterface $mentionRepository,
        private readonly SentimentAnalyzerInterface $sentimentAnalyzer,
    ) {}

    public function execute(string $mentionId): void
    {
        $mention = $this->mentionRepository->findById(Uuid::fromString($mentionId));

        if ($mention === null) {
            throw new MentionNotFoundException();
        }

        $result = $this->sentimentAnalyzer->analyze($mention->content);

        $mention = $mention->assignSentiment(
            sentiment: Sentiment::from($result->sentiment),
            score: $result->score,
        );

        $this->mentionRepository->update($mention);
    }
}
