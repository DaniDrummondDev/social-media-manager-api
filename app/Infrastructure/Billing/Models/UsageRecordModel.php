<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

final class UsageRecordModel extends Model
{
    protected $table = 'usage_records';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'organization_id',
        'resource_type',
        'quantity',
        'period_start',
        'period_end',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'period_start' => 'date:Y-m-d',
            'period_end' => 'date:Y-m-d',
            'recorded_at' => 'datetime',
        ];
    }
}
