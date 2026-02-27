<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum AdInsightType: string
{
    case BestAudiences = 'best_audiences';
    case BestContentForAds = 'best_content_for_ads';
    case OrganicVsPaidCorrelation = 'organic_vs_paid_correlation';

    public function label(): string
    {
        return match ($this) {
            self::BestAudiences => 'Melhores Audiencias',
            self::BestContentForAds => 'Melhor Conteudo para Ads',
            self::OrganicVsPaidCorrelation => 'Correlacao Organico vs Pago',
        };
    }
}
