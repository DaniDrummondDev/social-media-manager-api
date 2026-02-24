<?php

declare(strict_types=1);

namespace App\Application\Shared\Contracts;

use App\Domain\Shared\Events\DomainEvent;

interface EventDispatcherInterface
{
    public function dispatch(DomainEvent ...$events): void;
}
