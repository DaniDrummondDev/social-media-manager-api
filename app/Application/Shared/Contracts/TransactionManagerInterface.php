<?php

declare(strict_types=1);

namespace App\Application\Shared\Contracts;

interface TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;
}
