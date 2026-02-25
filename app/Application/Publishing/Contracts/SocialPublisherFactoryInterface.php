<?php

declare(strict_types=1);

namespace App\Application\Publishing\Contracts;

use App\Domain\SocialAccount\Contracts\SocialPublisherInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

interface SocialPublisherFactoryInterface
{
    public function make(SocialProvider $provider): SocialPublisherInterface;
}
