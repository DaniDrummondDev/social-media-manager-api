<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\AdInsightType;

it('has three cases', function () {
    expect(AdInsightType::cases())->toHaveCount(3);
});

it('has correct string values', function () {
    expect(AdInsightType::BestAudiences->value)->toBe('best_audiences')
        ->and(AdInsightType::BestContentForAds->value)->toBe('best_content_for_ads')
        ->and(AdInsightType::OrganicVsPaidCorrelation->value)->toBe('organic_vs_paid_correlation');
});

it('has correct labels', function () {
    expect(AdInsightType::BestAudiences->label())->toBe('Melhores Audiencias')
        ->and(AdInsightType::BestContentForAds->label())->toBe('Melhor Conteudo para Ads')
        ->and(AdInsightType::OrganicVsPaidCorrelation->label())->toBe('Correlacao Organico vs Pago');
});

it('creates from string value', function () {
    expect(AdInsightType::from('best_audiences'))->toBe(AdInsightType::BestAudiences)
        ->and(AdInsightType::from('best_content_for_ads'))->toBe(AdInsightType::BestContentForAds)
        ->and(AdInsightType::from('organic_vs_paid_correlation'))->toBe(AdInsightType::OrganicVsPaidCorrelation);
});
