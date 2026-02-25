<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Jobs;

use App\Application\ClientFinance\DTOs\GenerateMonthlyInvoicesInput;
use App\Application\ClientFinance\UseCases\GenerateMonthlyInvoicesUseCase;
use App\Infrastructure\ClientFinance\Models\ClientContractModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('client-finance');
    }

    public function handle(GenerateMonthlyInvoicesUseCase $useCase): void
    {
        $referenceMonth = now()->format('Y-m');

        /** @var \Illuminate\Support\Collection<int, string> $organizationIds */
        $organizationIds = (new ClientContractModel)->newQuery()
            ->where('status', 'active')
            ->distinct()
            ->pluck('organization_id');

        foreach ($organizationIds as $organizationId) {
            $generated = $useCase->execute(new GenerateMonthlyInvoicesInput(
                organizationId: $organizationId,
                userId: 'system',
                referenceMonth: $referenceMonth,
            ));

            if ($generated > 0) {
                Log::info('Monthly invoices generated', [
                    'organization_id' => $organizationId,
                    'reference_month' => $referenceMonth,
                    'count' => $generated,
                ]);
            }
        }
    }
}
