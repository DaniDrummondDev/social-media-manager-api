<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

enum QueryType: string
{
    case Keyword = 'keyword';
    case Hashtag = 'hashtag';
    case Mention = 'mention';
    case Competitor = 'competitor';
}
