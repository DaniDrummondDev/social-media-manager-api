<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class SsrfException extends ApplicationException
{
    public function __construct(string $url)
    {
        parent::__construct(
            message: "O URL do webhook resolve para um endereço IP privado ou reservado e foi bloqueado: {$url}",
            errorCode: 'WEBHOOK_SSRF_BLOCKED',
        );
    }
}
