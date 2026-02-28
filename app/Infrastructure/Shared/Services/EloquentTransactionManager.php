<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Services;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class EloquentTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
