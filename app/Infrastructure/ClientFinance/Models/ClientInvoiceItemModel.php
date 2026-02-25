<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Models;

use Illuminate\Database\Eloquent\Model;

final class ClientInvoiceItemModel extends Model
{
    protected $table = 'client_invoice_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_invoice_id',
        'description',
        'quantity',
        'unit_price_cents',
        'total_cents',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'total_cents' => 'integer',
            'position' => 'integer',
        ];
    }
}
