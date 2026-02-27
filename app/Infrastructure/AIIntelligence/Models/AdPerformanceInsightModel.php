<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AdPerformanceInsightModel extends Model
{
    use HasUuids;

    protected $table = 'ad_performance_insights';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'ad_insight_type',
        'insight_data',
        'sample_size',
        'confidence_level',
        'period_start',
        'period_end',
        'generated_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sample_size' => 'integer',
            'insight_data' => 'array',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
