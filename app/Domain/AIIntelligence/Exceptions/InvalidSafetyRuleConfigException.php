<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidSafetyRuleConfigException extends DomainException
{
    public function __construct(string $message = 'Invalid safety rule configuration.')
    {
        parent::__construct($message, 'INVALID_SAFETY_RULE_CONFIG');
    }
}
