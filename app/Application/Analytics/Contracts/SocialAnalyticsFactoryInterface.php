<?php

declare(strict_types=1);

namespace App\Application\Analytics\Contracts;

use App\Domain\SocialAccount\Contracts\SocialAnalyticsInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

interface SocialAnalyticsFactoryInterface
{
    public function make(SocialProvider $provider): SocialAnalyticsInterface;
}
