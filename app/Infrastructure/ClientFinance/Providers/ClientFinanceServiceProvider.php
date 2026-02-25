<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Providers;

use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\Repositories\CostAllocationRepositoryInterface;
use App\Infrastructure\ClientFinance\Repositories\EloquentClientContractRepository;
use App\Infrastructure\ClientFinance\Repositories\EloquentClientInvoiceRepository;
use App\Infrastructure\ClientFinance\Repositories\EloquentClientRepository;
use App\Infrastructure\ClientFinance\Repositories\EloquentCostAllocationRepository;
use Illuminate\Support\ServiceProvider;

final class ClientFinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ClientRepositoryInterface::class, EloquentClientRepository::class);
        $this->app->bind(ClientContractRepositoryInterface::class, EloquentClientContractRepository::class);
        $this->app->bind(ClientInvoiceRepositoryInterface::class, EloquentClientInvoiceRepository::class);
        $this->app->bind(CostAllocationRepositoryInterface::class, EloquentCostAllocationRepository::class);
    }
}
