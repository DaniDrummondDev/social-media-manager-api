<?php

declare(strict_types=1);

namespace App\Application\SocialListening\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ListeningReportNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Relatório de listening não encontrado.')
    {
        parent::__construct($message, 'LISTENING_REPORT_NOT_FOUND');
    }
}
