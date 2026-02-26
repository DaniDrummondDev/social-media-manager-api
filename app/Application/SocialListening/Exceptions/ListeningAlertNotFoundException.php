<?php

declare(strict_types=1);

namespace App\Application\SocialListening\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ListeningAlertNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Alerta de listening não encontrado.')
    {
        parent::__construct($message, 'LISTENING_ALERT_NOT_FOUND');
    }
}
