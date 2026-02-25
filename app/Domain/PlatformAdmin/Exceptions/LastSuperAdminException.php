<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class LastSuperAdminException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'Não é possível remover o último super admin da plataforma.',
            'LAST_SUPER_ADMIN',
        );
    }
}
