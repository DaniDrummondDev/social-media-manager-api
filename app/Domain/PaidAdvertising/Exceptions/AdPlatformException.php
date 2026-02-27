<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AdPlatformException extends DomainException
{
    public function __construct(string $message = 'Erro na plataforma de anuncios.', string $errorCode = 'AD_PLATFORM_ERROR')
    {
        parent::__construct(
            message: $message,
            errorCode: $errorCode,
        );
    }
}
