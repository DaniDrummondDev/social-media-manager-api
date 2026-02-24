<?php

declare(strict_types=1);

namespace App\Domain\Organization\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidOrganizationNameException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Organization name must be between 1 and 200 characters',
            errorCode: 'INVALID_ORGANIZATION_NAME',
        );
    }
}
