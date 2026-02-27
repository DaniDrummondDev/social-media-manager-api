<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AdAccountNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(
            message: "Conta de anuncios '{$id}' nao encontrada.",
            errorCode: 'AD_ACCOUNT_NOT_FOUND',
        );
    }
}
