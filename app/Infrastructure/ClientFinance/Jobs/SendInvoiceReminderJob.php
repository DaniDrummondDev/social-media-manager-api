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

final class SendInvoiceReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('client-finance');
    }

    public function handle(): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientInvoiceModel> $overdueInvoices */
        $overdueInvoices = (new ClientInvoiceModel)->newQuery()
            ->where('status', 'overdue')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            Log::info('Invoice reminder sent', [
                'invoice_id' => $invoice->getAttribute('id'),
                'client_id' => $invoice->getAttribute('client_id'),
                'organization_id' => $invoice->getAttribute('organization_id'),
                'total_cents' => $invoice->getAttribute('total_cents'),
                'due_date' => $invoice->getAttribute('due_date'),
            ]);
        }
    }
}
