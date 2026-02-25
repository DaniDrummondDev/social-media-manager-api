<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AdminUserNotFoundException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Usuário não encontrado.', 'ADMIN_USER_NOT_FOUND');
    }
}
