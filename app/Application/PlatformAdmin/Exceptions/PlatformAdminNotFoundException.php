<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class PlatformAdminNotFoundException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Administrador da plataforma não encontrado.', 'PLATFORM_ADMIN_NOT_FOUND');
    }
}
