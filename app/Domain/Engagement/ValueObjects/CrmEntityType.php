<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum CrmEntityType: string
{
    case Contact = 'contact';
    case Deal = 'deal';
    case Activity = 'activity';
}
