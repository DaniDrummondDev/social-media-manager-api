<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

enum ResourceType: string
{
    case Campaign = 'campaign';
    case AiGeneration = 'ai_generation';
    case MediaStorage = 'media_storage';
    case Publication = 'publication';
}
