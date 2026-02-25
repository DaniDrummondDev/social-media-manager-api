<?php

declare(strict_types=1);

namespace App\Application\Engagement\Contracts;

use App\Domain\SocialAccount\Contracts\SocialEngagementInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

interface SocialEngagementFactoryInterface
{
    public function make(SocialProvider $provider): SocialEngagementInterface;
}
