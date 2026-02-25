<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class InsufficientAdminPrivilegeException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Permissão insuficiente para esta ação.', 'INSUFFICIENT_ADMIN_PRIVILEGE');
    }
}
