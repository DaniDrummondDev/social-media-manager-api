<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class DuplicateAudienceNameException extends ApplicationException
{
    public function __construct(string $name)
    {
        parent::__construct("Audience with name '{$name}' already exists in this organization.", 'DUPLICATE_AUDIENCE_NAME');
    }
}
