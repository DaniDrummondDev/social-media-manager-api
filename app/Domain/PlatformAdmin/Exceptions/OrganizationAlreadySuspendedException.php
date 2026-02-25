<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class OrganizationAlreadySuspendedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'A organização já está suspensa.',
            'ORGANIZATION_ALREADY_SUSPENDED',
        );
    }
}
