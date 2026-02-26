<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class PostingTimeRecommendationModel extends Model
{
    use HasUuids;

    protected $table = 'posting_time_recommendations';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'social_account_id',
        'provider',
        'day_of_week',
        'heatmap',
        'top_slots',
        'worst_slots',
        'sample_size',
        'confidence_level',
        'calculated_at',
        'expires_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'heatmap' => 'array',
            'top_slots' => 'array',
            'worst_slots' => 'array',
            'day_of_week' => 'integer',
            'sample_size' => 'integer',
            'calculated_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
