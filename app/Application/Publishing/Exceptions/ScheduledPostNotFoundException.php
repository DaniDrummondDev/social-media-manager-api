<?php

declare(strict_types=1);

namespace App\Application\Publishing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ScheduledPostNotFoundException extends ApplicationException
{
    public function __construct(string $scheduledPostId)
    {
        parent::__construct("Scheduled post {$scheduledPostId} not found.", 'SCHEDULED_POST_NOT_FOUND');
    }
}
