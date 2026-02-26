<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Models;

use Illuminate\Database\Eloquent\Model;

final class GenerationFeedbackModel extends Model
{
    protected $table = 'generation_feedback';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'ai_generation_id',
        'action',
        'original_output',
        'edited_output',
        'diff_summary',
        'content_id',
        'generation_type',
        'time_to_decision_ms',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_output' => 'array',
            'edited_output' => 'array',
            'diff_summary' => 'array',
            'time_to_decision_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
