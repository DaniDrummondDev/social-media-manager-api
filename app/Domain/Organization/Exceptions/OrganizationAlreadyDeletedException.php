<?php

declare(strict_types=1);

namespace App\Domain\Organization\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class OrganizationAlreadyDeletedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Organization is already deleted',
            errorCode: 'ORGANIZATION_ALREADY_DELETED',
        );
    }
}
