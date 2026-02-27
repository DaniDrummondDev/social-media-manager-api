<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AudienceNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(
            message: "Audiencia '{$id}' nao encontrada.",
            errorCode: 'AUDIENCE_NOT_FOUND',
        );
    }
}
