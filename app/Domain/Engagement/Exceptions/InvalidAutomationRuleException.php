<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidAutomationRuleException extends DomainException
{
    public function __construct(string $message = 'Regra de automação inválida.')
    {
        parent::__construct($message, 'INVALID_AUTOMATION_RULE');
    }
}
