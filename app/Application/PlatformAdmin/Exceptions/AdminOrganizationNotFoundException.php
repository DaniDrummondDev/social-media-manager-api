<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AdminOrganizationNotFoundException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Organização não encontrada.', 'ADMIN_ORGANIZATION_NOT_FOUND');
    }
}
