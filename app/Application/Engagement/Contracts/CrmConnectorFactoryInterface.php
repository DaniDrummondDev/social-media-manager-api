<?php

declare(strict_types=1);

namespace App\Application\Engagement\Contracts;

use App\Domain\Engagement\Contracts\CrmConnectorInterface;
use App\Domain\Engagement\ValueObjects\CrmProvider;

interface CrmConnectorFactoryInterface
{
    public function make(CrmProvider $provider): CrmConnectorInterface;
}
