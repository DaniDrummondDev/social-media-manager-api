<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Jobs;

use App\Infrastructure\ClientFinance\Models\ClientInvoiceModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CheckOverdueInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('client-finance');
    }

    public function handle(): void
    {
        $count = (new ClientInvoiceModel)->newQuery()
            ->where('status', 'sent')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        if ($count > 0) {
            Log::info('Overdue invoices marked', [
                'count' => $count,
            ]);
        }
    }
}
