<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

final class InvoiceModel extends Model
{
    protected $table = 'invoices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'subscription_id',
        'external_invoice_id',
        'amount_cents',
        'currency',
        'status',
        'invoice_url',
        'period_start',
        'period_end',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
