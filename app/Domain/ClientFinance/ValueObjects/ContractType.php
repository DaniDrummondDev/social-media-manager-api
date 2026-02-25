<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

enum ContractType: string
{
    case FixedMonthly = 'fixed_monthly';
    case PerCampaign = 'per_campaign';
    case PerPost = 'per_post';
    case Hourly = 'hourly';
}
