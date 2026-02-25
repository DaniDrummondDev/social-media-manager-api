<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ClientModel extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'email',
        'phone',
        'company_name',
        'tax_id',
        'tax_id_type',
        'billing_address',
        'notes',
        'status',
        'purge_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'purge_at' => 'datetime',
        ];
    }
}
