<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Providers;

use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Repositories\InvoiceRepositoryInterface;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\StripeWebhookEventRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Infrastructure\Billing\Repositories\EloquentInvoiceRepository;
use App\Infrastructure\Billing\Repositories\EloquentPlanRepository;
use App\Infrastructure\Billing\Repositories\EloquentStripeWebhookEventRepository;
use App\Infrastructure\Billing\Repositories\EloquentSubscriptionRepository;
use App\Infrastructure\Billing\Repositories\EloquentUsageRecordRepository;
use App\Infrastructure\Billing\Services\StubPaymentGateway;
use Illuminate\Support\ServiceProvider;

final class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlanRepositoryInterface::class, EloquentPlanRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(UsageRecordRepositoryInterface::class, EloquentUsageRecordRepository::class);
        $this->app->bind(StripeWebhookEventRepositoryInterface::class, EloquentStripeWebhookEventRepository::class);
        $this->app->bind(PaymentGatewayInterface::class, StubPaymentGateway::class);
    }
}
