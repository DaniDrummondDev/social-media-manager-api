<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Models;

use Illuminate\Database\Eloquent\Model;

final class AdMetricSnapshotModel extends Model
{
    protected $table = 'ad_metric_snapshots';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'boost_id',
        'period',
        'impressions',
        'reach',
        'clicks',
        'spend_cents',
        'spend_currency',
        'conversions',
        'ctr',
        'cpc',
        'cpm',
        'cost_per_conversion',
        'captured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'impressions' => 'integer',
            'reach' => 'integer',
            'clicks' => 'integer',
            'spend_cents' => 'integer',
            'conversions' => 'integer',
            'ctr' => 'float',
            'cpc' => 'float',
            'cpm' => 'float',
            'cost_per_conversion' => 'float',
            'captured_at' => 'datetime',
        ];
    }
}
