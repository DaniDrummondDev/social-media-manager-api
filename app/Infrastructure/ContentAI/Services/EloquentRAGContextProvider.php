<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\ContentAI\Contracts\RAGContextProviderInterface;
use App\Application\ContentAI\DTOs\RAGContextResult;
use App\Infrastructure\Campaign\Models\ContentModel;
use App\Infrastructure\ContentAI\Models\ContentEmbeddingModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EloquentRAGContextProvider implements RAGContextProviderInterface
{
    private const MIN_CONTENTS_FOR_RAG = 5;

    private const TOKENS_PER_EXAMPLE = 100;

    public function __construct(
        private readonly EmbeddingGeneratorInterface $embeddingGenerator,
    ) {}

    public function retrieve(
        string $organizationId,
        string $topic,
        ?string $provider = null,
        int $limit = 5,
    ): RAGContextResult {
        // Check if we have enough embeddings for RAG
        $embeddingCount = ContentEmbeddingModel::where('organization_id', $organizationId)->count();

        if ($embeddingCount < self::MIN_CONTENTS_FOR_RAG) {
            Log::debug('RAG: Not enough embeddings for retrieval', [
                'organization_id' => $organizationId,
                'embedding_count' => $embeddingCount,
                'min_required' => self::MIN_CONTENTS_FOR_RAG,
            ]);

            return new RAGContextResult(
                contentIds: [],
                formattedExamples: '',
                tokenCount: 0,
            );
        }

        // Generate embedding for the topic
        $topicEmbedding = $this->embeddingGenerator->generate($topic);
        $embeddingVector = $this->formatEmbeddingForQuery($topicEmbedding);

        // Calculate median engagement rate for the organization
        $medianEngagementRate = $this->calculateMedianEngagementRate($organizationId);

        // Query similar content using pgvector cosine similarity
        $similarContents = $this->querySimilarContents(
            $organizationId,
            $embeddingVector,
            $medianEngagementRate,
            $provider,
            $limit,
        );

        if ($similarContents->isEmpty()) {
            return new RAGContextResult(
                contentIds: [],
                formattedExamples: '',
                tokenCount: 0,
            );
        }

        $contentIds = $similarContents->pluck('content_id')->toArray();
        $formattedExamples = $this->formatExamples($similarContents);
        $tokenCount = count($similarContents) * self::TOKENS_PER_EXAMPLE;

        return new RAGContextResult(
            contentIds: $contentIds,
            formattedExamples: $formattedExamples,
            tokenCount: $tokenCount,
        );
    }

    /**
     * @param  array<float>  $embedding
     */
    private function formatEmbeddingForQuery(array $embedding): string
    {
        return '['.implode(',', $embedding).']';
    }

    private function calculateMedianEngagementRate(string $organizationId): float
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::selectOne("
                SELECT PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY cms.engagement_rate) AS median_engagement
                FROM content_metric_snapshots cms
                JOIN content_metrics cm ON cm.id = cms.content_metric_id
                JOIN scheduled_posts sp ON sp.id = cm.scheduled_post_id
                JOIN contents c ON c.id = sp.content_id
                WHERE c.organization_id = ?
                AND cms.captured_at >= NOW() - INTERVAL '90 days'
            ", [$organizationId]);

            return (float) ($result->median_engagement ?? 0.0);
        }

        // Fallback for non-PostgreSQL: simple average (for testing)
        $result = DB::selectOne("
            SELECT AVG(cms.engagement_rate) AS avg_engagement
            FROM content_metric_snapshots cms
            JOIN content_metrics cm ON cm.id = cms.content_metric_id
            JOIN scheduled_posts sp ON sp.id = cm.scheduled_post_id
            JOIN contents c ON c.id = sp.content_id
            WHERE c.organization_id = ?
        ", [$organizationId]);

        return (float) ($result->avg_engagement ?? 0.0);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function querySimilarContents(
        string $organizationId,
        string $embeddingVector,
        float $medianEngagementRate,
        ?string $provider,
        int $limit,
    ): \Illuminate\Support\Collection {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return $this->querySimilarContentsPostgres(
                $organizationId,
                $embeddingVector,
                $medianEngagementRate,
                $provider,
                $limit,
            );
        }

        // Fallback for non-PostgreSQL (for testing)
        return $this->querySimilarContentsFallback(
            $organizationId,
            $medianEngagementRate,
            $provider,
            $limit,
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function querySimilarContentsPostgres(
        string $organizationId,
        string $embeddingVector,
        float $medianEngagementRate,
        ?string $provider,
        int $limit,
    ): \Illuminate\Support\Collection {
        $providerFilter = $provider !== null
            ? 'AND sp.provider = ?'
            : '';

        $params = [$organizationId, $medianEngagementRate];
        if ($provider !== null) {
            $params[] = $provider;
        }
        $params[] = $embeddingVector;
        $params[] = $limit;

        $sql = "
            SELECT
                ce.content_id,
                c.title,
                c.body,
                c.hashtags,
                cms.engagement_rate,
                ce.embedding <=> ?::vector AS distance
            FROM content_embeddings ce
            JOIN contents c ON c.id = ce.content_id
            JOIN scheduled_posts sp ON sp.content_id = c.id
            JOIN content_metrics cm ON cm.scheduled_post_id = sp.id
            JOIN (
                SELECT content_metric_id, MAX(engagement_rate) AS engagement_rate
                FROM content_metric_snapshots
                GROUP BY content_metric_id
            ) cms ON cms.content_metric_id = cm.id
            WHERE ce.organization_id = ?
            AND c.status = 'published'
            AND cms.engagement_rate > ?
            {$providerFilter}
            ORDER BY distance ASC
            LIMIT ?
        ";

        // Reorder params: embedding first (for <=> operator), then rest
        $orderedParams = [$embeddingVector, $organizationId, $medianEngagementRate];
        if ($provider !== null) {
            $orderedParams[] = $provider;
        }
        $orderedParams[] = $limit;

        return collect(DB::select($sql, $orderedParams));
    }

    /**
     * Fallback query for non-PostgreSQL databases (for testing).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function querySimilarContentsFallback(
        string $organizationId,
        float $medianEngagementRate,
        ?string $provider,
        int $limit,
    ): \Illuminate\Support\Collection {
        $query = ContentModel::query()
            ->select('contents.id as content_id', 'contents.title', 'contents.body', 'contents.hashtags')
            ->join('content_embeddings as ce', 'ce.content_id', '=', 'contents.id')
            ->where('contents.organization_id', $organizationId)
            ->where('contents.status', 'published')
            ->limit($limit);

        if ($provider !== null) {
            $query->join('scheduled_posts as sp', 'sp.content_id', '=', 'contents.id')
                ->where('sp.provider', $provider);
        }

        return $query->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $contents
     */
    private function formatExamples(\Illuminate\Support\Collection $contents): string
    {
        $examples = [];

        foreach ($contents as $index => $content) {
            $hashtags = is_string($content->hashtags)
                ? json_decode($content->hashtags, true)
                : ($content->hashtags ?? []);

            $hashtagsStr = is_array($hashtags)
                ? implode(' ', array_map(fn ($h) => "#{$h}", $hashtags))
                : '';

            $examples[] = sprintf(
                "Example %d (engagement: %.2f%%):\nTitle: %s\nDescription: %s\nHashtags: %s",
                $index + 1,
                ($content->engagement_rate ?? 0) * 100,
                $content->title ?? '',
                mb_substr($content->body ?? '', 0, 200),
                $hashtagsStr,
            );
        }

        return implode("\n\n---\n\n", $examples);
    }
}
