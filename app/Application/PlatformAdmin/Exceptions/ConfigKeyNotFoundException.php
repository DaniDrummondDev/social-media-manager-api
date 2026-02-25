<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ConfigKeyNotFoundException extends ApplicationException
{
    public function __construct(string $key)
    {
        parent::__construct("Configuração '{$key}' não encontrada.", 'CONFIG_KEY_NOT_FOUND');
    }
}
