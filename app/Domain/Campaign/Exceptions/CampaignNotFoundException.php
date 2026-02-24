<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CampaignNotFoundException extends DomainException
{
    public function __construct(string $campaignId)
    {
        parent::__construct(
            message: "Campaign not found: {$campaignId}",
            errorCode: 'CAMPAIGN_NOT_FOUND',
        );
    }
}
