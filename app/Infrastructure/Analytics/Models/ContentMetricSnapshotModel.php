<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentMetricSnapshotModel extends Model
{
    protected $table = 'content_metric_snapshots';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'content_metric_id',
        'impressions',
        'reach',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'views',
        'watch_time_seconds',
        'engagement_rate',
        'captured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'impressions' => 'integer',
            'reach' => 'integer',
            'likes' => 'integer',
            'comments' => 'integer',
            'shares' => 'integer',
            'saves' => 'integer',
            'clicks' => 'integer',
            'views' => 'integer',
            'watch_time_seconds' => 'integer',
            'engagement_rate' => 'float',
        ];
    }
}
