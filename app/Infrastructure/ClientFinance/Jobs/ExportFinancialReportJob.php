<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ExportFinancialReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $format,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
    ) {
        $this->onQueue('client-finance');
    }

    public function handle(): void
    {
        // Placeholder for future PDF/CSV export implementation
        Log::info('Financial report export requested', [
            'organization_id' => $this->organizationId,
            'user_id' => $this->userId,
            'format' => $this->format,
            'from' => $this->from,
            'to' => $this->to,
        ]);
    }
}
