<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentMetricModel extends Model
{
    protected $table = 'content_metrics';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'content_id',
        'social_account_id',
        'provider',
        'external_post_id',
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
        'synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
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
