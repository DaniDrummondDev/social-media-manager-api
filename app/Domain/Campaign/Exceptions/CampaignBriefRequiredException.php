<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CampaignBriefRequiredException extends DomainException
{
    public function __construct(string $campaignId)
    {
        parent::__construct(
            message: "Campaign has no brief defined: {$campaignId}",
            errorCode: 'CAMPAIGN_BRIEF_REQUIRED',
        );
    }
}
