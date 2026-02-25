<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum ActionType: string
{
    case ReplyFixed = 'reply_fixed';
    case ReplyTemplate = 'reply_template';
    case ReplyAi = 'reply_ai';
    case SendWebhook = 'send_webhook';
}
