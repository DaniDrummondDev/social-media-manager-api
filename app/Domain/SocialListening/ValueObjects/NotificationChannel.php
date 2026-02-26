<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

enum NotificationChannel: string
{
    case Email = 'email';
    case Webhook = 'webhook';
    case InApp = 'in_app';
}
