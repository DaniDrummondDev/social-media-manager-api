<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

final class AccountMetricModel extends Model
{
    protected $table = 'account_metrics';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'social_account_id',
        'provider',
        'date',
        'followers_count',
        'followers_gained',
        'followers_lost',
        'profile_views',
        'reach',
        'impressions',
        'synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'synced_at' => 'datetime',
            'followers_count' => 'integer',
            'followers_gained' => 'integer',
            'followers_lost' => 'integer',
            'profile_views' => 'integer',
            'reach' => 'integer',
            'impressions' => 'integer',
        ];
    }
}
