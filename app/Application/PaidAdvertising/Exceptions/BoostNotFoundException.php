<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class BoostNotFoundException extends ApplicationException
{
    public function __construct(string $boostId)
    {
        parent::__construct("Boost '{$boostId}' not found.", 'BOOST_NOT_FOUND');
    }
}
