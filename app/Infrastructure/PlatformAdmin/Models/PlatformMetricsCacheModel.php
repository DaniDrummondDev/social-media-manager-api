<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformMetricsCacheModel extends Model
{
    protected $table = 'platform_metrics_cache';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'computed_at',
        'ttl_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'computed_at' => 'datetime',
            'ttl_seconds' => 'integer',
        ];
    }
}
