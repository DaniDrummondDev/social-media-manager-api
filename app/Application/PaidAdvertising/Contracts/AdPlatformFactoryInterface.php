<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Contracts;

use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;

interface AdPlatformFactoryInterface
{
    public function make(AdProvider $provider): AdPlatformInterface;
}
