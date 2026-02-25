<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Models;

use Illuminate\Database\Eloquent\Model;

final class CostAllocationModel extends Model
{
    protected $table = 'cost_allocations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'organization_id',
        'resource_type',
        'resource_id',
        'description',
        'cost_cents',
        'currency',
        'allocated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_cents' => 'integer',
            'allocated_at' => 'datetime',
        ];
    }
}
