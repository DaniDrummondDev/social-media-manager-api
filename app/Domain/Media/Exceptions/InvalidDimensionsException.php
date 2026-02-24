<?php

declare(strict_types=1);

namespace App\Domain\Media\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidDimensionsException extends DomainException
{
    public function __construct(int $width, int $height)
    {
        parent::__construct(
            message: "Invalid dimensions: {$width}x{$height}. Width and height must be positive.",
            errorCode: 'INVALID_DIMENSIONS',
        );
    }
}
