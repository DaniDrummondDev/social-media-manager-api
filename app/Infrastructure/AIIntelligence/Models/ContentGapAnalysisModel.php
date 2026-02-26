<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ContentGapAnalysisModel extends Model
{
    use HasUuids;

    protected $table = 'content_gap_analyses';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'competitor_query_ids',
        'analysis_period_start',
        'analysis_period_end',
        'our_topics',
        'competitor_topics',
        'gaps',
        'opportunities',
        'status',
        'generated_at',
        'expires_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'competitor_query_ids' => 'array',
            'our_topics' => 'array',
            'competitor_topics' => 'array',
            'gaps' => 'array',
            'opportunities' => 'array',
            'analysis_period_start' => 'datetime',
            'analysis_period_end' => 'datetime',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
