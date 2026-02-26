<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class PredictionValidationModel extends Model
{
    use HasUuids;

    protected $table = 'prediction_validations';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'prediction_id',
        'content_id',
        'provider',
        'predicted_score',
        'actual_engagement_rate',
        'actual_normalized_score',
        'absolute_error',
        'prediction_accuracy',
        'metrics_snapshot',
        'validated_at',
        'metrics_captured_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'predicted_score' => 'integer',
            'actual_engagement_rate' => 'float',
            'actual_normalized_score' => 'integer',
            'absolute_error' => 'integer',
            'prediction_accuracy' => 'float',
            'metrics_snapshot' => 'array',
            'validated_at' => 'datetime',
            'metrics_captured_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
