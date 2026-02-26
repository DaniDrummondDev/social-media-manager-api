<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Models;

use Illuminate\Database\Eloquent\Model;

final class PromptExperimentModel extends Model
{
    protected $table = 'prompt_experiments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'generation_type',
        'name',
        'status',
        'variant_a_id',
        'variant_b_id',
        'traffic_split',
        'min_sample_size',
        'variant_a_uses',
        'variant_a_accepted',
        'variant_b_uses',
        'variant_b_accepted',
        'winner_id',
        'confidence_level',
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'traffic_split' => 'float',
            'min_sample_size' => 'integer',
            'variant_a_uses' => 'integer',
            'variant_a_accepted' => 'integer',
            'variant_b_uses' => 'integer',
            'variant_b_accepted' => 'integer',
            'confidence_level' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
