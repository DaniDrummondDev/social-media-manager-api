<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AutomationRuleNotFoundException extends ApplicationException
{
    public function __construct(string $ruleId)
    {
        parent::__construct(
            message: "Regra de automação '{$ruleId}' não encontrada.",
            errorCode: 'AUTOMATION_RULE_NOT_FOUND',
        );
    }
}
