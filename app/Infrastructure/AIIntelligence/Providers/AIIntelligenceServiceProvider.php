<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Providers;

use App\Application\AIIntelligence\Contracts\AudienceInsightAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\BrandSafetyAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\ContentGapAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\Contracts\PredictionValidatorInterface;
use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;
use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Domain\AIIntelligence\Contracts\CrmIntelligenceProviderInterface;
use App\Domain\AIIntelligence\Repositories\AudienceInsightRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\BrandSafetyCheckRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\CrmConversionAttributionRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\OrgStyleProfileRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\PostingTimeRecommendationRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\PredictionValidationRepositoryInterface;
use App\Infrastructure\AIIntelligence\Repositories\EloquentAudienceInsightRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentBrandSafetyCheckRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentBrandSafetyRuleRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentCalendarSuggestionRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentContentGapAnalysisRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentCrmConversionAttributionRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentContentProfileRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentOrgStyleProfileRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentPerformancePredictionRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentPostingTimeRecommendationRepository;
use App\Infrastructure\AIIntelligence\Repositories\EloquentPredictionValidationRepository;
use App\Infrastructure\AIIntelligence\Services\StubAudienceInsightAnalyzer;
use App\Infrastructure\AIIntelligence\Services\StubBrandSafetyAnalyzer;
use App\Infrastructure\AIIntelligence\Services\StubCrmIntelligenceProvider;
use App\Infrastructure\AIIntelligence\Services\StubContentGapAnalyzer;
use App\Infrastructure\AIIntelligence\Services\StubEmbeddingGenerator;
use App\Infrastructure\AIIntelligence\Services\StubPredictionValidator;
use App\Infrastructure\AIIntelligence\Services\StubSimilaritySearch;
use App\Infrastructure\AIIntelligence\Services\StubStyleProfileAnalyzer;
use App\Domain\Engagement\Events\CrmContactSynced;
use App\Domain\Engagement\Events\CrmDealCreated;
use App\Infrastructure\AIIntelligence\Listeners\AttributeCrmConversionOnContactSynced;
use App\Infrastructure\AIIntelligence\Listeners\AttributeCrmConversionOnDealCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AIIntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PostingTimeRecommendationRepositoryInterface::class, EloquentPostingTimeRecommendationRepository::class);
        $this->app->bind(BrandSafetyCheckRepositoryInterface::class, EloquentBrandSafetyCheckRepository::class);
        $this->app->bind(BrandSafetyRuleRepositoryInterface::class, EloquentBrandSafetyRuleRepository::class);
        $this->app->bind(BrandSafetyAnalyzerInterface::class, StubBrandSafetyAnalyzer::class);
        $this->app->bind(CalendarSuggestionRepositoryInterface::class, EloquentCalendarSuggestionRepository::class);
        $this->app->bind(ContentProfileRepositoryInterface::class, EloquentContentProfileRepository::class);
        $this->app->bind(PerformancePredictionRepositoryInterface::class, EloquentPerformancePredictionRepository::class);
        $this->app->bind(EmbeddingGeneratorInterface::class, StubEmbeddingGenerator::class);
        $this->app->bind(SimilaritySearchInterface::class, StubSimilaritySearch::class);
        $this->app->bind(AudienceInsightRepositoryInterface::class, EloquentAudienceInsightRepository::class);
        $this->app->bind(ContentGapAnalysisRepositoryInterface::class, EloquentContentGapAnalysisRepository::class);
        $this->app->bind(AudienceInsightAnalyzerInterface::class, StubAudienceInsightAnalyzer::class);
        $this->app->bind(ContentGapAnalyzerInterface::class, StubContentGapAnalyzer::class);
        $this->app->bind(PredictionValidationRepositoryInterface::class, EloquentPredictionValidationRepository::class);
        $this->app->bind(OrgStyleProfileRepositoryInterface::class, EloquentOrgStyleProfileRepository::class);
        $this->app->bind(PredictionValidatorInterface::class, StubPredictionValidator::class);
        $this->app->bind(StyleProfileAnalyzerInterface::class, StubStyleProfileAnalyzer::class);
        $this->app->bind(CrmConversionAttributionRepositoryInterface::class, EloquentCrmConversionAttributionRepository::class);
        $this->app->bind(CrmIntelligenceProviderInterface::class, StubCrmIntelligenceProvider::class);
    }

    public function boot(): void
    {
        Event::listen(CrmDealCreated::class, AttributeCrmConversionOnDealCreated::class);
        Event::listen(CrmContactSynced::class, AttributeCrmConversionOnContactSynced::class);
    }
}
