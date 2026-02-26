<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\ProcessMentionsBatchInput;
use App\Application\SocialListening\Exceptions\ListeningQueryNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\Sentiment;
use DateTimeImmutable;

final class ProcessMentionsBatchUseCase
{
    public function __construct(
        private readonly MentionRepositoryInterface $mentionRepository,
        private readonly ListeningQueryRepositoryInterface $queryRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ProcessMentionsBatchInput $input): int
    {
        $queryId = Uuid::fromString($input->queryId);
        $organizationId = Uuid::fromString($input->organizationId);

        $query = $this->queryRepository->findById($queryId);

        if ($query === null || (string) $query->organizationId !== (string) $organizationId) {
            throw new ListeningQueryNotFoundException();
        }

        $newMentions = [];

        foreach ($input->mentionsData as $data) {
            $externalId = $data['external_id'];
            $platform = $data['platform'];

            if ($this->mentionRepository->existsByExternalId($externalId, $platform)) {
                continue;
            }

            $newMentions[] = Mention::create(
                queryId: $queryId,
                organizationId: $query->organizationId,
                platform: $platform,
                externalId: $externalId,
                authorUsername: $data['author_username'],
                authorDisplayName: $data['author_display_name'],
                authorFollowerCount: $data['author_follower_count'] ?? null,
                profileUrl: $data['profile_url'] ?? null,
                content: $data['content'],
                url: $data['url'] ?? null,
                sentiment: isset($data['sentiment']) ? Sentiment::from($data['sentiment']) : null,
                sentimentScore: $data['sentiment_score'] ?? null,
                reach: $data['reach'] ?? 0,
                engagementCount: $data['engagement_count'] ?? 0,
                publishedAt: new DateTimeImmutable($data['published_at']),
            );
        }

        if (count($newMentions) > 0) {
            $this->mentionRepository->createBatch($newMentions);

            foreach ($newMentions as $mention) {
                $this->eventDispatcher->dispatch(...$mention->domainEvents);
            }
        }

        return count($newMentions);
    }
}
