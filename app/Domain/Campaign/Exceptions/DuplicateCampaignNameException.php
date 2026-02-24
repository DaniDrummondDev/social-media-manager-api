<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class DuplicateCampaignNameException extends DomainException
{
    public function __construct(string $name)
    {
        parent::__construct(
            message: "A campaign with the name '{$name}' already exists in this organization.",
            errorCode: 'DUPLICATE_CAMPAIGN_NAME',
        );
    }
}
