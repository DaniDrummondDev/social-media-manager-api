<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidConfigValueException extends DomainException
{
    public function __construct(string $key, string $expectedType)
    {
        parent::__construct(
            "Valor inválido para configuração '{$key}'. Tipo esperado: {$expectedType}.",
            'INVALID_CONFIG_VALUE',
        );
    }
}
