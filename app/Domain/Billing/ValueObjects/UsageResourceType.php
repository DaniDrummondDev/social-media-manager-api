<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

enum UsageResourceType: string
{
    case Publications = 'publications';
    case AiGenerations = 'ai_generations';
    case StorageBytes = 'storage_bytes';
    case Members = 'members';
    case SocialAccounts = 'social_accounts';
    case Campaigns = 'campaigns';
    case Automations = 'automations';
    case Webhooks = 'webhooks';
    case Reports = 'reports';
}
