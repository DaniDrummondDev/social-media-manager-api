<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class PerformancePredictionModel extends Model
{
    use HasUuids;

    protected $table = 'performance_predictions';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'content_id',
        'provider',
        'overall_score',
        'breakdown',
        'similar_content_ids',
        'recommendations',
        'model_version',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'breakdown' => 'array',
            'similar_content_ids' => 'array',
            'recommendations' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
