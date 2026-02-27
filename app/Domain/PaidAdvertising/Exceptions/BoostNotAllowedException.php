<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class BoostNotAllowedException extends DomainException
{
    public function __construct(string $message = 'Operacao de boost nao permitida.')
    {
        parent::__construct(
            message: $message,
            errorCode: 'BOOST_NOT_ALLOWED',
        );
    }
}
