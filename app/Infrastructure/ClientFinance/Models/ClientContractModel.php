<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Models;

use Illuminate\Database\Eloquent\Model;

final class ClientContractModel extends Model
{
    protected $table = 'client_contracts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'organization_id',
        'name',
        'type',
        'value_cents',
        'currency',
        'starts_at',
        'ends_at',
        'social_account_ids',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_cents' => 'integer',
            'social_account_ids' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
