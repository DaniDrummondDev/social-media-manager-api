<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidAdStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            message: "Transicao de status invalida: '{$from}' para '{$to}'.",
            errorCode: 'INVALID_AD_STATUS_TRANSITION',
        );
    }
}
