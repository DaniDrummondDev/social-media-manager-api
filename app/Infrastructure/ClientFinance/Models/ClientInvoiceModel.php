<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClientInvoiceModel extends Model
{
    protected $table = 'client_invoices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'contract_id',
        'organization_id',
        'reference_month',
        'subtotal_cents',
        'discount_cents',
        'total_cents',
        'currency',
        'status',
        'due_date',
        'paid_at',
        'sent_at',
        'payment_method',
        'payment_notes',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ClientInvoiceItemModel, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ClientInvoiceItemModel::class, 'client_invoice_id')->orderBy('position');
    }
}
